<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday;

use Closure;
use DOMDocument;
use DOMElement;
use DOMException;
use MagicSunday\XmlMapper\Annotation\XmlAttribute;
use MagicSunday\XmlMapper\Annotation\XmlCDataSection;
use MagicSunday\XmlMapper\Annotation\XmlIgnore;
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;
use MagicSunday\XmlMapper\Converter\PropertyNameConverterInterface;
use MagicSunday\XmlMapper\Exception\CircularReferenceException;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;
use Stringable;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\WrappingTypeInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

use function array_fill_keys;
use function array_key_exists;
use function array_pop;
use function implode;
use function is_array;
use function is_bool;
use function is_iterable;
use function is_scalar;
use function method_exists;
use function spl_object_id;
use function str_replace;

/**
 * XmlEncoder.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class XmlEncoder
{
    /**
     * The property marker annotations recognised by the encoder.
     *
     * @var list<class-string>
     */
    private const array MARKER_ANNOTATIONS = [
        XmlAttribute::class,
        XmlNodeValue::class,
        XmlCDataSection::class,
        XmlIgnore::class,
    ];

    /**
     * The document being built by the current map() call.
     */
    private DOMDocument $domDocument;

    /**
     * The objects that are being encoded on the current path, keyed by object id.
     * An object that is already in here and is reached again closes a cycle.
     *
     * @var array<int, true>
     */
    private array $activeObjects = [];

    /**
     * The property path from the root object to the property being encoded, one
     * entry per step. It is only used to name the path in an exception.
     *
     * @var list<string>
     */
    private array $propertyPath = [];

    /**
     * The default type instance.
     *
     * @var BuiltinType<TypeIdentifier::STRING>
     */
    private readonly BuiltinType $defaultType;

    /**
     * The custom types.
     *
     * @var array<string, Closure>
     */
    private array $types = [];

    /**
     * The resolved marker annotations memoised per "class::property" key.
     *
     * @var array<string, array<class-string, bool>>
     */
    private array $markerCache = [];

    /**
     * XmlEncoder constructor.
     *
     * @param PropertyInfoExtractorInterface      $extractor
     * @param PropertyNameConverterInterface|null $nameConverter A name converter instance
     */
    public function __construct(
        private readonly PropertyInfoExtractorInterface $extractor,
        /**
         * The property name converter instance.
         */
        protected ?PropertyNameConverterInterface $nameConverter = null,
    ) {
        $this->defaultType = new BuiltinType(TypeIdentifier::STRING);
    }

    /**
     * Add a custom type.
     *
     * @param string  $type    A fully qualified class name, or a builtin type name
     *                         ("bool", "int", "float", "string", "array", "object").
     *                         A class name takes precedence over the builtin key.
     * @param Closure $closure The closure to execute for the defined type
     *
     * @return $this
     */
    public function addType(string $type, Closure $closure): XmlEncoder
    {
        $this->types[$type] = $closure;

        return $this;
    }

    /**
     * Maps the given object instance to XML.
     *
     * @param XmlSerializable $instance
     *
     * @return string|false the XML, or false if an error occurred
     *
     * @throws DOMException
     * @throws CircularReferenceException When an object is reached again through its own properties
     */
    public function map(XmlSerializable $instance): string|false
    {
        // A fresh document per call: keeping one for the lifetime of the encoder
        // made a second call append another root element to the first result,
        // which is a truthy string that no XML parser accepts.
        //
        // The previous document is restored afterwards so a nested call — a
        // custom-type closure mapping a sub-object through the same encoder —
        // cannot pull the document out from under the outer run.
        //
        // A nested call is part of the outer encode, though. It keeps the set of
        // objects being encoded and extends the property path instead of starting
        // fresh, because a cycle that runs through a closure calling map() again
        // has to be found too. Whatever the nested call adds or leaves behind,
        // after an exception it caught for one, is handed back as it was.
        $previousDocument      = $this->domDocument ?? null;
        $previousActiveObjects = $this->activeObjects;
        $previousPropertyPath  = $this->propertyPath;

        try {
            $this->domDocument                = new DOMDocument('1.0', 'UTF-8');
            $this->domDocument->xmlStandalone = false;
            $this->domDocument->formatOutput  = true;

            $rootElementName      = $this->getClassShortName($instance);
            $this->propertyPath[] = $rootElementName;

            if ($this->nameConverter instanceof PropertyNameConverterInterface) {
                $rootElementName = $this->nameConverter->convert($rootElementName);
            }

            // Encode given object instance. Set the short name of class as surrounding XML tag
            $this->encodeObject(
                null,
                $rootElementName,
                $instance
            );

            return $this->domDocument->saveXML();
        } finally {
            $this->activeObjects = $previousActiveObjects;
            $this->propertyPath  = $previousPropertyPath;

            if ($previousDocument instanceof DOMDocument) {
                $this->domDocument = $previousDocument;
            } else {
                unset($this->domDocument);
            }
        }
    }

    /**
     * Recursively encodes the given class and its properties to XML. Ignores all
     * properties with a "null" value. Encodes each internal type to string.
     *
     * @param DOMElement      $domElement
     * @param XmlSerializable $instance
     *
     * @throws DOMException
     * @throws CircularReferenceException When an object is reached again through its own properties
     */
    private function encodeElement(DOMElement $domElement, XmlSerializable $instance): void
    {
        $className  = $instance::class;
        $properties = $this->extractor->getProperties($className) ?? [];
        $reflection = new ReflectionObject($instance);

        // Process all properties of the class
        foreach ($properties as $propertyName) {
            if (!$reflection->hasProperty($propertyName)) {
                continue;
            }

            // An ignored property is left alone before anything touches it: no
            // read, no custom type converter and no other marker applies.
            if ($this->hasPropertyAnnotation($className, $propertyName, XmlIgnore::class)) {
                continue;
            }

            $property = $reflection->getProperty($propertyName);

            // A static property is state of the class, shared by every instance,
            // and not state of the object being encoded.
            if ($property->isStatic()) {
                continue;
            }

            // A typed property that was never assigned raises a native Error on
            // read. That is outside every guarantee map() documents, and an
            // unset optional property is an ordinary DTO shape, so treat it the
            // same way as a null value: skip it.
            if (!$property->isInitialized($instance)) {
                continue;
            }

            // A write-only virtual property reports itself as initialized, yet
            // reading it raises a native Error as well, so it is skipped too.
            if ($this->isWriteOnly($property)) {
                continue;
            }

            $propertyValue = $property->getValue($instance);
            $propertyType  = $this->getType($className, $propertyName);
            $customTypeKey = $this->getCustomTypeKey($propertyType);

            if ($customTypeKey !== null) {
                // The property is on the path while its converter runs, so a nested
                // map() call from the converter reads as a step below this property.
                $this->propertyPath[] = $propertyName;

                $propertyValue = $this->callCustomClosure(
                    $propertyName,
                    $propertyValue,
                    $customTypeKey
                );

                array_pop($this->propertyPath);
            }

            // Ignore null values
            if ($propertyValue === null) {
                continue;
            }

            // Convert property name according name converter
            $xmlPropertyName = $this->nameConverter instanceof PropertyNameConverterInterface
                ? $this->nameConverter->convert($propertyName)
                : $propertyName;

            // Process attributes
            if ($this->hasPropertyAnnotation($className, $propertyName, XmlAttribute::class)) {
                $domElement
                    ->setAttribute(
                        $xmlPropertyName,
                        $this->encodeValue($propertyValue)
                    );

                continue;
            }

            // Process CDATA section
            if ($this->hasPropertyAnnotation($className, $propertyName, XmlCDataSection::class)) {
                $domElement
                    ->appendChild(
                        $this->domDocument->createCDATASection(
                            $this->encodeValue($propertyValue)
                        )
                    );

                continue;
            }

            // Process raw text node
            if ($this->hasPropertyAnnotation($className, $propertyName, XmlNodeValue::class)) {
                $domElement
                    ->appendChild(
                        $this->domDocument->createTextNode(
                            $this->encodeValue($propertyValue)
                        )
                    );

                continue;
            }

            $this->propertyPath[] = $propertyName;

            // Process collections, then any other data
            if ($this->isCollection($propertyType)) {
                $this->encodeCollection(
                    $domElement,
                    $xmlPropertyName,
                    $propertyValue
                );
            } else {
                $this->encodeObjectOrScalar(
                    $domElement,
                    $xmlPropertyName,
                    $propertyValue
                );
            }

            array_pop($this->propertyPath);
        }
    }

    /**
     * Strips nullability wrappers (e.g. "?Foo") from the given type while preserving
     * its collection and object semantics.
     *
     * @param Type $type
     *
     * @return Type
     */
    private function getBaseType(Type $type): Type
    {
        // Never unwrap a collection: it is itself a WrappingTypeInterface and
        // unwrapping it would discard the collection semantics.
        while (($type instanceof WrappingTypeInterface) && !($type instanceof CollectionType)) {
            $type = $type->getWrappedType();
        }

        return $type;
    }

    /**
     * Maps an already-unwrapped base type onto its builtin name, so object and
     * collection types resolve to "object" and "array" respectively.
     *
     * This is the fallback key {@see getCustomTypeKey()} falls back to once the
     * class-name lookup has missed — it is no longer the lookup itself.
     *
     * @param Type $baseType The property type with its nullable wrapper already
     *                       removed by {@see getBaseType()}
     *
     * @return string
     */
    private function getBuiltinTypeName(Type $baseType): string
    {
        if ($baseType instanceof BuiltinType) {
            return $baseType->getTypeIdentifier()->value;
        }

        if ($baseType instanceof CollectionType) {
            return TypeIdentifier::ARRAY->value;
        }

        if ($baseType instanceof ObjectType) {
            return TypeIdentifier::OBJECT->value;
        }

        // Union, intersection and other composite types have no single builtin
        // name, so they fall back to "string" as the custom-closure lookup key.
        return TypeIdentifier::STRING->value;
    }

    /**
     * Returns TRUE if the given type describes a collection.
     *
     * @param Type $type
     *
     * @return bool
     */
    private function isCollection(Type $type): bool
    {
        return $this->getBaseType($type) instanceof CollectionType;
    }

    /**
     * Returns TRUE if the property can only be written, which is a virtual
     * property that has a set hook and no get hook.
     *
     * @param ReflectionProperty $property The property to check
     *
     * @return bool
     */
    private function isWriteOnly(ReflectionProperty $property): bool
    {
        // Neither property hooks nor the reflection of them exist below PHP 8.4,
        // and no class can declare such a property there.
        if (
            !method_exists($property, 'isVirtual')
            || !method_exists($property, 'getHooks')
        ) {
            return false;
        }

        $hooks = $property->getHooks();

        return ($property->isVirtual() === true)
            && is_array($hooks)
            && !array_key_exists('get', $hooks);
    }

    /**
     * Returns TRUE if the property carries the given marker, applied as a native
     * PHP attribute (#[XmlAttribute]).
     *
     * @param class-string $className      The class name of the initial element
     * @param string       $propertyName   The name of the property
     * @param class-string $annotationName The name of the property annotation
     *
     * @return bool
     */
    private function hasPropertyAnnotation(string $className, string $propertyName, string $annotationName): bool
    {
        return $this->resolvePropertyMarkers($className, $propertyName)[$annotationName] ?? false;
    }

    /**
     * Resolves which marker attributes a property carries and memoises the result
     * per "class::property" key, so the reflection lookup runs once per property
     * rather than once per marker check per encoded object.
     *
     * @param class-string $className    The class name of the initial element
     * @param string       $propertyName The name of the property
     *
     * @return array<class-string, bool>
     */
    private function resolvePropertyMarkers(string $className, string $propertyName): array
    {
        $cacheKey = $className . '::' . $propertyName;

        if (isset($this->markerCache[$cacheKey])) {
            return $this->markerCache[$cacheKey];
        }

        $markers = array_fill_keys(self::MARKER_ANNOTATIONS, false);

        $reflectionProperty = $this->getReflectionProperty($className, $propertyName);

        if (!$reflectionProperty instanceof ReflectionProperty) {
            return $this->markerCache[$cacheKey] = $markers;
        }

        // The marker classes are final, so an exact attribute-name match is
        // equivalent to an instanceof check: read all attributes once and flag
        // the markers present, rather than querying reflection per marker.
        foreach ($reflectionProperty->getAttributes() as $attribute) {
            $name = $attribute->getName();

            if (isset($markers[$name])) {
                $markers[$name] = true;
            }
        }

        return $this->markerCache[$cacheKey] = $markers;
    }

    /**
     * Returns the specified reflection property.
     *
     * @param class-string $className    The class name of the initial element
     * @param string       $propertyName The name of the property
     */
    private function getReflectionProperty(string $className, string $propertyName): ?ReflectionProperty
    {
        try {
            return new ReflectionProperty($className, $propertyName);
        } catch (ReflectionException) {
            return null;
        }
    }

    /**
     * Determine the type for the specified property using reflection.
     *
     * @param class-string $class
     * @param string       $propertyName
     *
     * @return Type
     */
    private function getType(string $class, string $propertyName): Type
    {
        return $this->extractor->getType($class, $propertyName) ?? $this->defaultType;
    }

    /**
     * Processes a collection and encodes each entry into XML.
     *
     * Converts:
     *
     *       $property = array[
     *           'value1',
     *           'value2'
     *       ]
     *
     *  to
     *
     *       <property>value1</property>
     *       <property>value2</property>
     *
     * @param string $name   The XML node name
     * @param mixed  $values The collection values to encode to the XML
     *
     * @throws DOMException
     * @throws CircularReferenceException When an object is reached again through its own properties
     */
    private function encodeCollection(DOMElement $parent, string $name, mixed $values): void
    {
        if (!is_iterable($values)) {
            return;
        }

        // Process all entries in the collection. The position is counted here
        // rather than read from the keys, because asking an iterator for its key
        // is a call its entries were never subject to before.
        $position = 0;

        foreach ($values as $value) {
            $this->propertyPath[] = '[' . $position . ']';

            $this->encodeObjectOrScalar($parent, $name, $value);

            array_pop($this->propertyPath);

            ++$position;
        }
    }

    /**
     * Encodes an object or scalar value into XML. The object-versus-scalar
     * decision is made from the runtime value rather than the declared type, so
     * a union- or intersection-typed property (whose declared type cannot name a
     * single class) still encodes each entry by its real type.
     *
     * @param string $name  The XML node name
     * @param mixed  $value The XML node value
     *
     * @throws DOMException
     * @throws CircularReferenceException When an object is reached again through its own properties
     */
    private function encodeObjectOrScalar(DOMElement $parent, string $name, mixed $value): void
    {
        if ($value instanceof XmlSerializable) {
            $this->encodeObject($parent, $name, $value);
        } else {
            // The value goes in as a text node rather than through the second
            // argument of createElement(): that argument is not escaped, so an
            // ampersand truncated the rest of the value and an already-encoded
            // value lost a level of escaping on every round trip.
            $element = $this->domDocument->createElement($name);
            $encoded = $this->encodeValue($value);

            // Only append a text node when there is text: an empty one would
            // turn `<name/>` into `<name></name>` for every value that encodes
            // to an empty string.
            if ($encoded !== '') {
                $element->appendChild(
                    $this->domDocument->createTextNode($encoded)
                );
            }

            $parent->appendChild($element);
        }
    }

    /**
     * Encodes an object into XML.
     *
     * @param DOMElement|null $parent If NULL the newly created element is added directly to the document
     * @param string          $name   The XML node name
     * @param XmlSerializable $value  The XML node value
     *
     * @throws DOMException
     * @throws CircularReferenceException When the object is already being encoded further up the same path
     */
    private function encodeObject(?DOMElement $parent, string $name, XmlSerializable $value): void
    {
        $objectId = spl_object_id($value);

        // Only an object that is still being encoded on the way down to here is a
        // cycle. The same instance on another branch has finished by the time it
        // is reached again, so it is no longer in the set.
        if (isset($this->activeObjects[$objectId])) {
            throw CircularReferenceException::atPath(
                $this->describePropertyPath(),
                $value::class
            );
        }

        $this->activeObjects[$objectId] = true;

        $node = $this->domDocument->createElement($name);

        // Encode object and its properties
        $this->encodeElement($node, $value);

        unset($this->activeObjects[$objectId]);

        if ($parent instanceof DOMElement) {
            $parent->appendChild($node);
        } else {
            $this->domDocument->appendChild($node);
        }
    }

    /**
     * Returns the property path that leads to the object being reached, written
     * as "Root.property.property" with the index of a collection entry in
     * brackets after its property.
     *
     * @return string
     */
    private function describePropertyPath(): string
    {
        return str_replace('.[', '[', implode('.', $this->propertyPath));
    }

    /**
     * Encodes the given scalar value to its string representation. Booleans are
     * rendered as their integer value; anything that is neither scalar nor
     * Stringable yields an empty string.
     *
     * @return string
     */
    private function encodeValue(mixed $propertyValue): string
    {
        if (is_bool($propertyValue)) {
            return (string) (int) $propertyValue;
        }

        if (is_scalar($propertyValue) || ($propertyValue instanceof Stringable)) {
            return (string) $propertyValue;
        }

        return '';
    }

    /**
     * Returns the short name of the given class instance.
     *
     * @param XmlSerializable $instance
     *
     * @return string
     */
    private function getClassShortName(XmlSerializable $instance): string
    {
        return (new ReflectionClass($instance))->getShortName();
    }

    /**
     * Determine if the specified type is a custom type.
     *
     * @param string $typeName
     *
     * @return bool
     */
    private function isCustomType(string $typeName): bool
    {
        return array_key_exists($typeName, $this->types);
    }

    /**
     * Returns the key under which a custom type is registered for the given
     * property type, or NULL when none applies.
     *
     * The fully qualified class name is tried first so a converter can target
     * one value object; the builtin name stays available as the catch-all.
     * Without the first step every object collapses to "object", which makes a
     * converter for a single class impossible to express.
     *
     * @param Type $type The resolved property type
     *
     * @return string|null The registration key, or NULL when no custom type applies
     */
    private function getCustomTypeKey(Type $type): ?string
    {
        $baseType = $this->getBaseType($type);

        if (($baseType instanceof ObjectType) && $this->isCustomType($baseType->getClassName())) {
            return $baseType->getClassName();
        }

        $builtinType = $this->getBuiltinTypeName($baseType);

        return $this->isCustomType($builtinType) ? $builtinType : null;
    }

    /**
     * Call the custom closure for the specified type.
     *
     * @param string $propertyName
     * @param string $typeName
     */
    private function callCustomClosure(string $propertyName, mixed $propertyValue, string $typeName): mixed
    {
        $callback = $this->types[$typeName];

        return $callback($propertyName, $propertyValue);
    }
}
