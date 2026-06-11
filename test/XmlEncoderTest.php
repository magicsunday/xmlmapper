<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test;

use MagicSunday\Test\Fixture\Author;
use MagicSunday\Test\Fixture\Book;
use MagicSunday\Test\Fixture\Chapter;
use MagicSunday\Test\Fixture\Comment;
use MagicSunday\Test\Fixture\CustomTypeHost;
use MagicSunday\Test\Fixture\Person;
use MagicSunday\Test\Fixture\Price;
use MagicSunday\Test\Fixture\UnionProperty;
use MagicSunday\XmlEncoder;
use MagicSunday\XmlMapper\Annotation\XmlAttribute;
use MagicSunday\XmlMapper\Annotation\XmlCDataSection;
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;
use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * Behavioural characterization tests pinning the XML output produced by the encoder.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
#[CoversClass(XmlEncoder::class)]
#[UsesClass(CamelCasePropertyNameConverter::class)]
#[UsesClass(XmlAttribute::class)]
#[UsesClass(XmlNodeValue::class)]
#[UsesClass(XmlCDataSection::class)]
class XmlEncoderTest extends TestCase
{
    /**
     * Scalar properties are encoded as child elements, booleans become integers
     * and null values are skipped entirely.
     */
    #[Test]
    public function encodesScalarPropertiesAndSkipsNull(): void
    {
        $xml = $this->getXmlEncoder()->map(new Person());

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <person>
                    <firstName>John</firstName>
                    <homeTown>Berlin</homeTown>
                    <age>42</age>
                    <active>1</active>
                </person>
                XML,
            (string) $xml
        );
    }

    /**
     * Nested objects and populated nullable objects are encoded recursively.
     */
    #[Test]
    public function encodesNestedAndNullableObjects(): void
    {
        $book = new Book();

        $book->author         = new Author();
        $book->coAuthor       = new Author();
        $book->coAuthor->name = 'John Roe';
        $book->chapters       = [
            new Chapter('Intro', 1),
            new Chapter('Body', 2),
        ];

        $xml = $this->getXmlEncoder()->map($book);

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <book isbn="978-3-16-148410-0">
                    <title>The Title</title>
                    <author>
                        <name>Jane Doe</name>
                    </author>
                    <coAuthor>
                        <name>John Roe</name>
                    </coAuthor>
                    <tags>php</tags>
                    <tags>xml</tags>
                    <chapters>
                        <heading>Intro</heading>
                        <number>1</number>
                    </chapters>
                    <chapters>
                        <heading>Body</heading>
                        <number>2</number>
                    </chapters>
                    <misc>a</misc>
                    <misc>b</misc>
                </book>
                XML,
            (string) $xml
        );
    }

    /**
     * A property annotated with XmlAttribute becomes an attribute while a property
     * annotated with XmlNodeValue becomes the raw element content.
     */
    #[Test]
    public function encodesAttributeAndNodeValue(): void
    {
        $xml = $this->getXmlEncoder()->map(new Price());

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><price currency="EUR">42.00</price>',
            (string) $xml
        );
    }

    /**
     * A property annotated with XmlCDataSection is wrapped in a CDATA section,
     * leaving its markup unescaped.
     */
    #[Test]
    public function encodesCDataSection(): void
    {
        $xml = (string) $this->getXmlEncoder()->map(new Comment());

        self::assertStringContainsString('<comment><![CDATA[<b>hi</b>]]></comment>', $xml);
    }

    /**
     * A registered custom type closure transforms every value of the matching
     * builtin type before it is written.
     */
    #[Test]
    public function appliesCustomTypeClosure(): void
    {
        $encoder = $this->getXmlEncoder();
        $encoder->addType('bool', static fn (string $name, mixed $value): string => $value === true ? 'yes' : 'no');

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <person>
                    <firstName>John</firstName>
                    <homeTown>Berlin</homeTown>
                    <age>42</age>
                    <active>yes</active>
                </person>
                XML,
            $encoder->map(new Person())
        );
    }

    /**
     * Custom type closures registered under the "array" and "object" builtin type
     * names are applied to collection and object properties respectively.
     */
    #[Test]
    public function appliesCustomTypeClosureForArrayAndObjectKeys(): void
    {
        $host         = new CustomTypeHost();
        $host->author = new Author();

        $encoder = $this->getXmlEncoder();
        $encoder->addType('array', static fn (string $name, array $value): array => array_map('strtoupper', $value));
        $encoder->addType('object', static function (string $name, Author $value): Author {
            $value->name = strtoupper($value->name);

            return $value;
        });

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <customTypeHost>
                    <items>A</items>
                    <items>B</items>
                    <author>
                        <name>JANE DOE</name>
                    </author>
                </customTypeHost>
                XML,
            $encoder->map($host)
        );
    }

    /**
     * A populated nullable collection is unwrapped to its collection type and each
     * entry is encoded, proving the nullable wrapper is stripped without losing the
     * collection semantics.
     */
    #[Test]
    public function encodesPopulatedNullableCollection(): void
    {
        $book         = new Book();
        $book->author = new Author();
        $book->labels = ['draft', 'review'];

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <book isbn="978-3-16-148410-0">
                    <title>The Title</title>
                    <author>
                        <name>Jane Doe</name>
                    </author>
                    <tags>php</tags>
                    <tags>xml</tags>
                    <labels>draft</labels>
                    <labels>review</labels>
                    <misc>a</misc>
                    <misc>b</misc>
                </book>
                XML,
            $this->getXmlEncoder()->map($book)
        );
    }

    /**
     * Without a name converter the raw class and property names are used verbatim
     * as element names.
     */
    #[Test]
    public function encodesWithoutNameConverter(): void
    {
        $extractor = new PropertyInfoExtractor([new ReflectionExtractor()], [new PhpDocExtractor()]);
        $encoder   = new XmlEncoder($extractor);

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <Person>
                    <firstName>John</firstName>
                    <home_town>Berlin</home_town>
                    <age>42</age>
                    <active>1</active>
                </Person>
                XML,
            $encoder->map(new Person())
        );
    }

    /**
     * A union of scalar types resolves to its first member and is encoded through
     * the scalar path.
     */
    #[Test]
    public function encodesUnionTypedPropertyAsScalar(): void
    {
        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><unionProperty><code>7</code></unionProperty>',
            $this->getXmlEncoder()->map(new UnionProperty())
        );
    }
}
