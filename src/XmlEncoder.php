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
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;
use MagicSunday\XmlMapper\Converter\PropertyNameConverterInterface;
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
use function is_bool;
use function is_iterable;
use function is_scalar;

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
    ];

    /**
     * The document being built by the current map() call.
     *
     * @var DOMDocument
     */
    private DOMDocument $domDocument;

    /**
     * The default type instance.
     *
     * @var BuiltinType<TypeIdentifier::STRING>
     */
    private BuiltinType $defaultType;

    /**
     * @var PropertyInfoExtractorInterface
     */
    private PropertyInfoExtractorInterface $extractor;

    /**
     * The property name converter instance.
     *
     * @var PropertyNameConverterInterface|null
     */
    protected ?PropertyNameConverterInterface $nameConverter;

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
        PropertyInfoExtractorInterface $extractor,
        ?PropertyNameConverterInterface $nameConverter = null,
    ) {
        $this->defaultType   = new BuiltinType(TypeIdentifier::STRING);
        $this->extractor     = $extractor;
        $this->nameConverter = $nameConverter;
    }

    /**
     * Add a custom type.
     *
     * @param string  $type    The type name
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
        $previousDocument = $this->domDocument ?? null;

        try {
            $this->domDocument                = new DOMDocument('1.0', 'UTF-8');
            $this->domDocument->xmlStandalone = false;
            $this->domDocument->formatOutput  = true;

            $rootElementName = $this->getClassShortName($instance);

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
     */
    private function encodeElement(DOMElement $domElement, XmlSerializable $instance): void
    {
        /** @var class-string $className */
        $className  = $this->getClassName($instance);
        $properties = $this->extractor->getProperties($className) ?? [];
        $reflection = new ReflectionObject($instance);

        // Process all properties of the class
        foreach ($properties as $propertyName) {
            if (!$reflection->hasProperty($propertyName)) {
                continue;
            }

            $property      = $reflection->getProperty($propertyName);
            $propertyValue = $property->getValue($instance);
            $propertyType  = $this->getType($className, $propertyName);
            $builtinType   = $this->getBuiltinTypeName($propertyType);

            if ($this->isCustomType($builtinType)) {
                $propertyValue = $this->callCustomClosure(
                    $propertyName,
                    $propertyValue,
                    $builtinType
                );
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
            if ($this->isXmlAttributeAnnotation($className, $propertyName)) {
                $domElement
                    ->setAttribute(
                        $xmlPropertyName,
                        $this->encodeValue($propertyValue)
                    );

                continue;
            }

            // Process CDATA section
            if ($this->isXmlCDataSectionAnnotation($className, $propertyName)) {
                $domElement
                    ->appendChild(
                        $this->domDocument->createCDATASection(
                            $this->encodeValue($propertyValue)
                        )
                    );

                continue;
            }

            // Process raw text node
            if ($this->isXmlNodeValueAnnotation($className, $propertyName)) {
                $domElement
                    ->appendChild(
                        $this->domDocument->createTextNode(
                            $this->encodeValue($propertyValue)
                        )
                    );

                continue;
            }

            // Process collections
            if ($this->isCollection($propertyType)) {
                $this->encodeCollection(
                    $domElement,
                    $xmlPropertyName,
                    $propertyValue
                );

                continue;
            }

            // Process any other data
            $this->encodeObjectOrScalar(
                $domElement,
                $xmlPropertyName,
                $propertyValue
            );
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
     * Returns the builtin type name used to look up custom type closures. Custom
     * type closures are registered under the builtin type name, so object and
     * collection types resolve to "object" and "array" respectively.
     *
     * @param Type $type
     *
     * @return string
     */
    private function getBuiltinTypeName(Type $type): string
    {
        $baseType = $this->getBaseType($type);

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
     * Returns TRUE if the property contains an "XmlAttribute" annotation.
     *
     * @param class-string $className    The class name of the initial element
     * @param string       $propertyName The name of the property
     *
     * @return bool
     */
    private function isXmlAttributeAnnotation(string $className, string $propertyName): bool
    {
        return $this->hasPropertyAnnotation(
            $className,
            $propertyName,
            XmlAttribute::class
        );
    }

    /**
     * Returns TRUE if the property contains an "XmlNodeValue" annotation.
     *
     * @param class-string $className    The class name of the initial element
     * @param string       $propertyName The name of the property
     *
     * @return bool
     */
    private function isXmlNodeValueAnnotation(string $className, string $propertyName): bool
    {
        return $this->hasPropertyAnnotation(
            $className,
            $propertyName,
            XmlNodeValue::class
        );
    }

    /**
     * Returns TRUE if the property contains an "XmlCDataSection" annotation.
     *
     * @param class-string $className    The class name of the initial element
     * @param string       $propertyName The name of the property
     *
     * @return bool
     */
    private function isXmlCDataSectionAnnotation(string $className, string $propertyName): bool
    {
        return $this->hasPropertyAnnotation(
            $className,
            $propertyName,
            XmlCDataSection::class
        );
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
     */
    private function encodeCollection(DOMElement $parent, string $name, mixed $values): void
    {
        if (!is_iterable($values)) {
            return;
        }

        // Process all entries in the collection
        foreach ($values as $value) {
            $this->encodeObjectOrScalar($parent, $name, $value);
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
     */
    private function encodeObjectOrScalar(DOMElement $parent, string $name, mixed $value): void
    {
        if ($value instanceof XmlSerializable) {
            $this->encodeObject($parent, $name, $value);
        } else {
            // Write encoded value directly into XML output
            $parent->appendChild(
                $this->domDocument->createElement(
                    $name,
                    $this->encodeValue($value)
                )
            );
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
     */
    private function encodeObject(?DOMElement $parent, string $name, XmlSerializable $value): void
    {
        $node = $this->domDocument->createElement($name);

        // Encode object and its properties
        $this->encodeElement($node, $value);

        if ($parent instanceof DOMElement) {
            $parent->appendChild($node);
        } else {
            $this->domDocument->appendChild($node);
        }
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
     * Returns the name of the given class instance.
     *
     * @param XmlSerializable $instance
     *
     * @return class-string
     */
    private function getClassName(XmlSerializable $instance): string
    {
        return (new ReflectionClass($instance))->getName();
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
