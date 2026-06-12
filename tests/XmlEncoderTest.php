<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test;

use Doctrine\Common\Annotations\AnnotationException;
use MagicSunday\Test\Fixture\Author;
use MagicSunday\Test\Fixture\BodyHost;
use MagicSunday\Test\Fixture\Book;
use MagicSunday\Test\Fixture\Chapter;
use MagicSunday\Test\Fixture\Comment;
use MagicSunday\Test\Fixture\CustomTypeHost;
use MagicSunday\Test\Fixture\ForeignDocblockOnly;
use MagicSunday\Test\Fixture\NativeCData;
use MagicSunday\Test\Fixture\NativeMarkers;
use MagicSunday\Test\Fixture\NativeWithForeignDocblock;
use MagicSunday\Test\Fixture\Person;
use MagicSunday\Test\Fixture\Price;
use MagicSunday\Test\Fixture\UnionObjectHost;
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
     * The XmlAttribute and XmlNodeValue markers are also recognised when applied
     * with the native PHP 8 attribute syntax, producing the same output as the
     * Doctrine docblock annotation.
     */
    #[Test]
    public function encodesNativeAttributeAndNodeValueMarkers(): void
    {
        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><nativeMarkers currency="EUR">42.00</nativeMarkers>',
            (string) $this->getXmlEncoder()->map(new NativeMarkers())
        );
    }

    /**
     * The XmlCDataSection marker is also recognised when applied with the native
     * PHP 8 attribute syntax.
     */
    #[Test]
    public function encodesNativeCDataSectionMarker(): void
    {
        $xml = (string) $this->getXmlEncoder()->map(new NativeCData());

        self::assertStringContainsString('<nativeCData><![CDATA[<b>hi</b>]]></nativeCData>', $xml);
    }

    /**
     * A property that uses a native marker and additionally carries an unrelated,
     * unimported docblock annotation is encoded without failing, regardless of
     * which marker is queried first.
     */
    #[Test]
    public function encodesNativeMarkerAlongsideForeignDocblockAnnotation(): void
    {
        $xml = (string) $this->getXmlEncoder()->map(new NativeWithForeignDocblock());

        self::assertStringContainsString(
            '<nativeWithForeignDocblock><![CDATA[<b>hi</b>]]></nativeWithForeignDocblock>',
            $xml
        );
    }

    /**
     * A docblock-only property whose annotation the reader cannot resolve fails
     * loudly instead of being silently mis-encoded, so a fumbled marker (e.g. a
     * forgotten use import) is not swallowed.
     */
    #[Test]
    public function failsLoudlyForUnresolvableDocblockWithoutNativeMarker(): void
    {
        $this->expectException(AnnotationException::class);

        $this->getXmlEncoder()->map(new ForeignDocblockOnly());
    }

    /**
     * Two classes that share a property name but carry divergent markers are
     * resolved independently within one document: the marker cache is keyed by
     * class and property, not by property name alone. Comment::body is a CDATA
     * section while PlainBody::body has no marker, so the latter's markup is
     * escaped rather than served Comment's cached CDATA map (and vice versa).
     */
    #[Test]
    public function resolvesMarkersPerClassNotByPropertyNameAlone(): void
    {
        $xml = (string) $this->getXmlEncoder()->map(new BodyHost());

        // Comment::body keeps its CDATA section ...
        self::assertStringContainsString('<![CDATA[<b>hi</b>]]>', $xml);

        // ... while PlainBody::body, sharing the property name, is escaped as a
        // plain element. A property-name-only cache key would collapse both to
        // the same marker and break one of these.
        self::assertStringContainsString('<body>&lt;b&gt;hi&lt;/b&gt;</body>', $xml);
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
            (string) $encoder->map(new Person())
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
        $encoder->addType('array', static function (string $name, array $value): array {
            /** @var string[] $value */
            return array_map('strtoupper', $value);
        });
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
            (string) $encoder->map($host)
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
            (string) $this->getXmlEncoder()->map($book)
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
            (string) $encoder->map(new Person())
        );
    }

    /**
     * A scalar-valued union-typed property is encoded through the scalar path,
     * because its runtime value is not an XmlSerializable object.
     */
    #[Test]
    public function encodesUnionTypedPropertyAsScalar(): void
    {
        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><unionProperty><code>7</code></unionProperty>',
            (string) $this->getXmlEncoder()->map(new UnionProperty())
        );
    }

    /**
     * A union or intersection type has no single builtin name, so a custom type
     * closure for such a property is dispatched through the "string" key rather
     * than a member type. This pins the dispatch key for composite-typed
     * properties.
     */
    #[Test]
    public function appliesCustomTypeClosureToUnionTypedPropertyViaStringKey(): void
    {
        $encoder = $this->getXmlEncoder();
        $encoder->addType('string', static fn (string $name, int|string $value): string => 'wrapped:' . $value);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><unionProperty><code>wrapped:7</code></unionProperty>',
            (string) $encoder->map(new UnionProperty())
        );
    }

    /**
     * A collection whose value type is a union of object types encodes every
     * entry recursively as an object, because the object-versus-scalar decision
     * is made from each runtime value rather than the union type (which cannot
     * name a single class).
     */
    #[Test]
    public function encodesUnionOfObjectTypes(): void
    {
        $host          = new UnionObjectHost();
        $host->members = [new Author(), new Chapter('X', 9)];

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <unionObjectHost>
                    <members>
                        <name>Jane Doe</name>
                    </members>
                    <members>
                        <heading>X</heading>
                        <number>9</number>
                    </members>
                </unionObjectHost>
                XML,
            (string) $this->getXmlEncoder()->map($host)
        );
    }

    /**
     * A collection whose value type is a union mixing a scalar and an object
     * type encodes each entry by its runtime value: scalars through the scalar
     * path and objects recursively. This guards against routing every entry
     * through one branch based on the static union type.
     */
    #[Test]
    public function encodesCollectionWithMixedScalarAndObjectValues(): void
    {
        $host        = new UnionObjectHost();
        $host->mixed = [42, new Author()];

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <unionObjectHost>
                    <mixed>42</mixed>
                    <mixed>
                        <name>Jane Doe</name>
                    </mixed>
                </unionObjectHost>
                XML,
            (string) $this->getXmlEncoder()->map($host)
        );
    }
}
