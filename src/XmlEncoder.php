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
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
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
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

use function array_key_exists;
use function is_bool;

/**
 * XmlEncoder.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 *
 * @template TEntity
 * @template TEntityCollection
 */
class XmlEncoder
{
    /**
     * XMLWriter instance.
     *
     * @var DOMDocument
     */
    private DOMDocument $domDocument;

    /**
     * The default type instance.
     *
     * @var Type
     */
    private Type $defaultType;

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
     * XmlEncoder constructor.
     *
     * @param PropertyInfoExtractorInterface      $extractor
     * @param PropertyNameConverterInterface|null $nameConverter A name converter instance
     */
    public function __construct(
        PropertyInfoExtractorInterface $extractor,
        ?PropertyNameConverterInterface $nameConverter = null,
    ) {
        $this->domDocument                = new DOMDocument('1.0', 'UTF-8');
        $this->domDocument->xmlStandalone = false;
        $this->domDocument->formatOutput  = true;

        $this->defaultType   = new Type(Type::BUILTIN_TYPE_STRING);
        $this->extractor     = $extractor;
        $this->nameConverter = $nameConverter;
    }

    /**
     * Add a custom type.
     *
     * @param string  $type    The type name
     * @param Closure $closure The closure to execute for the defined type
     *
     * @return XmlEncoder
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
        /** @var class-string<TEntity> $className */
        $className  = $this->getClassName($instance);
        $properties = $this->extractor->getProperties($className) ?? [];

        // Process all properties of the class
        foreach ($properties as $propertyName) {
            $reflection = new ReflectionObject($instance);

            if (!$reflection->hasProperty($propertyName)) {
                continue;
            }

            $property      = $reflection->getProperty($propertyName);
            $propertyValue = $property->getValue($instance);
            $propertyType  = $this->getType($className, $propertyName);

            if ($this->isCustomType($propertyType->getBuiltinType())) {
                $propertyValue = $this->callCustomClosure(
                    $propertyName,
                    $propertyValue,
                    $propertyType->getBuiltinType()
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
            if ($propertyType->isCollection()) {
                $this->encodeCollection(
                    $domElement,
                    $this->getCollectionValueType($propertyType),
                    $xmlPropertyName,
                    $propertyValue
                );

                continue;
            }

            // Process any other data
            $this->encodeObjectOrScalar(
                $domElement,
                $propertyType,
                $xmlPropertyName,
                $propertyValue
            );
        }
    }

    /**
     * Gets collection value type.
     *
     * @param Type $type
     *
     * @return Type
     */
    private function getCollectionValueType(Type $type): Type
    {
        $collectionValueType = $type->getCollectionValueTypes()[0] ?? null;

        return $collectionValueType ?? $this->defaultType;
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
     * Returns TRUE if the property has the given annotation.
     *
     * @param class-string $className      The class name of the initial element
     * @param string       $propertyName   The name of the property
     * @param string       $annotationName The name of the property annotation
     *
     * @return bool
     */
    private function hasPropertyAnnotation(string $className, string $propertyName, string $annotationName): bool
    {
        $annotations = $this->extractPropertyAnnotations($className, $propertyName);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extracts possible property annotations.
     *
     * @param class-string $className    The class name of the initial element
     * @param string       $propertyName The name of the property
     *
     * @return Annotation[]|object[]
     */
    private function extractPropertyAnnotations(string $className, string $propertyName): array
    {
        $reflectionProperty = $this->getReflectionProperty($className, $propertyName);

        if ($reflectionProperty instanceof ReflectionProperty) {
            return (new AnnotationReader())
                ->getPropertyAnnotations($reflectionProperty);
        }

        return [];
    }

    /**
     * Returns the specified reflection property.
     *
     * @param class-string $className    The class name of the initial element
     * @param string       $propertyName The name of the property
     *
     * @return ReflectionProperty|null
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
     * @param string $class
     * @param string $propertyName
     *
     * @return Type
     */
    private function getType(string $class, string $propertyName): Type
    {
        return $this->extractor->getTypes($class, $propertyName)[0] ?? $this->defaultType;
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
     * @param Type   $type   The type value object
     * @param string $name   The XML node name
     * @param mixed  $values The collection values to encode to the XML
     *
     * @throws DOMException
     */
    private function encodeCollection(DOMElement $parent, Type $type, string $name, mixed $values): void
    {
        // Process all entries in the collection
        foreach ($values as $value) {
            $this->encodeObjectOrScalar($parent, $type, $name, $value);
        }
    }

    /**
     * Encodes an object or scalar value into XML.
     *
     * @param Type   $type  The type value object
     * @param string $name  The XML node name
     * @param mixed  $value The XML node value
     *
     * @throws DOMException
     */
    private function encodeObjectOrScalar(DOMElement $parent, Type $type, string $name, mixed $value): void
    {
        if ($type->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT) {
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
     * Encodes the given value to a string.
     *
     * @param mixed $propertyValue
     *
     * @return string
     */
    private function encodeValue(mixed $propertyValue): string
    {
        $value = is_bool($propertyValue) ? (int) $propertyValue : $propertyValue;

        return (string) $value;
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
     * @param mixed  $propertyValue
     * @param string $typeName
     *
     * @return mixed
     */
    private function callCustomClosure(string $propertyName, mixed $propertyValue, string $typeName): mixed
    {
        $callback = $this->types[$typeName];

        return $callback($propertyName, $propertyValue);
    }
}
