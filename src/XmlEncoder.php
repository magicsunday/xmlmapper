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
use MagicSunday\XmlMapper\Annotation\XmlAttribute;
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;
use MagicSunday\XmlMapper\Converter\PropertyNameConverterInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use ReflectionProperty;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Type;
use XMLWriter;

use function array_key_exists;
use function is_bool;

/**
 * XmlEncoder
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
     * @var XMLWriter
     */
    private XMLWriter $xml;

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
     * @var null|PropertyNameConverterInterface
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
     * @param null|PropertyNameConverterInterface $nameConverter A name converter instance
     */
    public function __construct(
        PropertyInfoExtractorInterface $extractor,
        PropertyNameConverterInterface $nameConverter = null,
    ) {
        $this->xml = new XMLWriter();
        $this->xml->openMemory();
        $this->xml->setIndent(true);
        $this->xml->setIndentString('    ');
        $this->xml->startDocument('1.0', 'UTF-8');

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
     * @return string
     */
    public function map(XmlSerializable $instance): string
    {
        $rootElementName = $this->getClassShortName($instance);

        if ($this->nameConverter) {
            $rootElementName = $this->nameConverter->convert($rootElementName);
        }

        // Encode given object instance. Set the short name of class as surrounding XML tag
        $this->encodeObject(
            $rootElementName,
            $instance
        );

        $this->xml->endDocument();

        return $this->xml->outputMemory();
    }

    /**
     * Recursively encodes the given class and its properties to XML. Ignores all
     * properties with a "null" value. Encodes each internal type to string.
     *
     * @param XmlSerializable $instance
     */
    private function encodeElement(XmlSerializable $instance): void
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
            if ($this->nameConverter) {
                $xmlPropertyName = $this->nameConverter->convert($propertyName);
            } else {
                $xmlPropertyName = $propertyName;
            }

            // Process attributes
            if ($this->isXmlAttributeAnnotation($className, $propertyName)) {
                $this->xml
                    ->writeAttribute(
                        $xmlPropertyName,
                        $this->encodeValue($propertyValue)
                    );

                continue;
            }

            // Process raw text node
            if ($this->isXmlNodeValueAnnotation($className, $propertyName)) {
                $this->xml
                    ->writeRaw(
                        $this->encodeValue($propertyValue)
                    );

                continue;
            }

            // Process collections
            if ($propertyType->isCollection()) {
                $this->encodeCollection(
                    $this->getCollectionValueType($propertyType),
                    $xmlPropertyName,
                    $propertyValue
                );

                continue;
            }

            // Process any other data
            $this->encodeObjectOrScalar(
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
        // BC for symfony < 5.3
        if (!method_exists($type, 'getCollectionValueTypes')) {
            $collectionValueType = $type->getCollectionValueType();
        } else {
            $collectionValueType = $type->getCollectionValueTypes()[0] ?? null;
        }

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
     * Returns TRUE if the property has the given annotation
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
     * @return Annotation[]
     */
    private function extractPropertyAnnotations(string $className, string $propertyName): array
    {
        $reflectionProperty = $this->getReflectionProperty($className, $propertyName);
        $annotations        = [];

        if ($reflectionProperty) {
            /** @var Annotation[] $annotations */
            $annotations = (new AnnotationReader())
                ->getPropertyAnnotations($reflectionProperty);
        }

        return $annotations;
    }

    /**
     * Returns the specified reflection property.
     *
     * @param class-string $className    The class name of the initial element
     * @param string       $propertyName The name of the property
     *
     * @return null|ReflectionProperty
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
     */
    private function encodeCollection(Type $type, string $name, $values): void
    {
        // Process all entries in the collection
        foreach ($values as $value) {
            $this->encodeObjectOrScalar($type, $name, $value);
        }
    }

    /**
     * Encodes an object or scalar value into XML.
     *
     * @param Type   $type  The type value object
     * @param string $name  The XML node name
     * @param mixed  $value The XML node value
     */
    private function encodeObjectOrScalar(Type $type, string $name, mixed $value): void
    {
        if ($type->getBuiltinType() === Type::BUILTIN_TYPE_OBJECT) {
            $this->encodeObject($name, $value);
        } else {
            // Write encoded value directly into XML output
            $this->xml->writeElement(
                $name,
                $this->encodeValue($value)
            );
        }
    }

    /**
     * Encodes an object into XML.
     *
     * @param string          $name  The XML node name
     * @param XmlSerializable $value The XML node value
     */
    private function encodeObject(string $name, XmlSerializable $value): void
    {
        $this->xml->startElement($name);

        // Encode object and its properties
        $this->encodeElement($value);

        $this->xml->endElement();
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
        if (is_bool($propertyValue)) {
            $value = (int) $propertyValue;
        } else {
            $value = $propertyValue;
        }

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
