<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test;

use DOMDocument;
use DOMElement;
use DOMException;
use MagicSunday\Test\Fixture\Author;
use MagicSunday\Test\Fixture\BodyHost;
use MagicSunday\Test\Fixture\Book;
use MagicSunday\Test\Fixture\Chapter;
use MagicSunday\Test\Fixture\Comment;
use MagicSunday\Test\Fixture\CustomTypeHost;
use MagicSunday\Test\Fixture\EscapeCDataHost;
use MagicSunday\Test\Fixture\EscapeHost;
use MagicSunday\Test\Fixture\EscapeMarkerHost;
use MagicSunday\Test\Fixture\NativeCData;
use MagicSunday\Test\Fixture\NativeMarkers;
use MagicSunday\Test\Fixture\NativeWithForeignAttribute;
use MagicSunday\Test\Fixture\NativeWithForeignDocblock;
use MagicSunday\Test\Fixture\Person;
use MagicSunday\Test\Fixture\PlainBody;
use MagicSunday\Test\Fixture\Price;
use MagicSunday\Test\Fixture\UninitializedHost;
use MagicSunday\Test\Fixture\UnionObjectHost;
use MagicSunday\Test\Fixture\UnionProperty;
use MagicSunday\XmlEncoder;
use MagicSunday\XmlMapper\Annotation\XmlAttribute;
use MagicSunday\XmlMapper\Annotation\XmlCDataSection;
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;
use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;
use MagicSunday\XmlMapper\Converter\PropertyNameConverterInterface;
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
     * The XmlAttribute and XmlNodeValue markers are recognised when applied with
     * the native PHP 8 attribute syntax.
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
     * A property that uses a native marker and additionally carries an unrelated
     * docblock annotation from another library is encoded without failing: only
     * native attributes are inspected, so the stray docblock is simply ignored.
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
     * A foreign native attribute is ignored by the single-pass attribute scan: a
     * marker on the same property still resolves (currency becomes an attribute),
     * while a property carrying only the foreign attribute gets no marker and is
     * rendered as a plain child element. Mapped without a name converter, so the
     * raw (capitalised) class short name is used as the root element.
     */
    #[Test]
    public function ignoresForeignNativeAttributeWhileResolvingMarkers(): void
    {
        $extractor = new PropertyInfoExtractor([new ReflectionExtractor()], [new PhpDocExtractor()]);

        self::assertXmlStringEqualsXmlString(
            '<?xml version="1.0" encoding="UTF-8"?><NativeWithForeignAttribute currency="EUR"><label>x</label></NativeWithForeignAttribute>',
            (string) (new XmlEncoder($extractor))->map(new NativeWithForeignAttribute())
        );
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

    /**
     * Parses the encoder output back and returns the document element.
     *
     * Escaping cannot be asserted with assertXmlStringEqualsXmlString: that
     * assertion normalises entity spelling away, which is exactly the dimension
     * under test here.
     *
     * @param string $xml       Encoder output
     * @param string $onFailure Diagnostic shown when the output does not parse
     *
     * @return DOMElement
     */
    private function parseDocumentElement(
        string $xml,
        string $onFailure = 'Encoder produced XML that cannot be parsed back',
    ): DOMElement {
        $document = new DOMDocument();

        // Capture libxml errors internally: on the failure path loadXML() emits
        // a PHP warning, which the test runner turns into an exception before
        // the assertion below can report what actually went wrong.
        $previous = libxml_use_internal_errors(true);
        $loaded   = $document->loadXML($xml);

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        self::assertTrue($loaded, $onFailure);

        $documentElement = $document->documentElement;

        self::assertInstanceOf(DOMElement::class, $documentElement);

        return $documentElement;
    }

    /**
     * A value carrying XML-significant characters has to arrive unchanged. The
     * element path is the one that lost content: an unescaped ampersand
     * truncated everything after it.
     */
    #[Test]
    public function preservesSignificantCharactersOnTheElementPath(): void
    {
        $root = $this->parseDocumentElement(
            (string) $this->getXmlEncoder()->map(new EscapeHost())
        );

        self::assertSame(
            'Tom & <b>Jerry</b>',
            $root->getElementsByTagName('element')->item(0)?->textContent
        );
    }

    /**
     * An already-encoded value must not lose a level of escaping on the way
     * out, otherwise a round trip silently rewrites the data.
     */
    #[Test]
    public function preservesAnAlreadyEncodedValue(): void
    {
        $root = $this->parseDocumentElement(
            (string) $this->getXmlEncoder()->map(new EscapeHost())
        );

        self::assertSame(
            'x &amp; y',
            $root->getElementsByTagName('preencoded')->item(0)?->textContent
        );
    }

    /**
     * The attribute path already escaped correctly; pinning it keeps the two
     * paths from drifting apart again.
     */
    #[Test]
    public function preservesSignificantCharactersOnTheAttributePath(): void
    {
        $root = $this->parseDocumentElement(
            (string) $this->getXmlEncoder()->map(new EscapeHost())
        );

        self::assertSame('a & b < c', $root->getAttribute('attribute'));
    }

    /**
     * The text-node path likewise already escaped correctly.
     */
    #[Test]
    public function preservesSignificantCharactersOnTheTextNodePath(): void
    {
        $root = $this->parseDocumentElement(
            (string) $this->getXmlEncoder()->map(new EscapeMarkerHost())
        );

        self::assertSame('raw & <b>text</b>', $root->textContent);
        self::assertSame('EUR & GBP', $root->getAttribute('currency'));
    }

    /**
     * A value that encodes to an empty string stays a self-closing element.
     *
     * Pinned with an exact comparison: assertXmlStringEqualsXmlString treats
     * `<a/>` and `<a></a>` as equal, so it cannot see this at all.
     */
    #[Test]
    public function keepsAnEmptyValueSelfClosing(): void
    {
        $host       = new PlainBody();
        $host->body = '';

        self::assertStringContainsString(
            '<body/>',
            (string) $this->getXmlEncoder()->map($host)
        );
    }

    /**
     * The third write path.
     *
     * A CDATA section is not terminated by an entity but by the literal
     * sequence `]]>`, so escaping here is structural. The value is parsed back
     * rather than string-matched: libxml splits the sequence across two
     * sections, which a substring assertion would either miss or mis-report.
     */
    #[Test]
    public function preservesTheTerminatorSequenceInACDataSection(): void
    {
        $root = $this->parseDocumentElement(
            (string) $this->getXmlEncoder()->map(new EscapeCDataHost())
        );

        // A CDATA-marked property becomes the content of the class element
        // itself, so the value is read off the document element.
        self::assertSame('a]]>b', $root->textContent);
    }

    /**
     * The encoder takes its collaborators through the constructor and is
     * therefore a natural candidate for a container service, so a second call
     * has to produce the same document instead of appending a second root
     * element to the first one.
     */
    #[Test]
    public function mapIsRepeatableOnTheSameInstance(): void
    {
        $encoder = $this->getXmlEncoder();

        $first  = (string) $encoder->map(new Author());
        $second = (string) $encoder->map(new Author());

        self::assertSame($first, $second);

        // Two root elements would still be a truthy, non-empty string, so
        // parsing the result back is what actually discriminates here.
        $this->parseDocumentElement($second, 'A repeated map() call produced XML that cannot be parsed back');
    }

    /**
     * One exact-output assertion over a minimal fixture.
     *
     * The rest of the suite compares through assertXmlStringEqualsXmlString,
     * which normalises the declaration, whitespace and entity spelling away —
     * so nothing pinned the bytes that are actually delivered. Note the
     * `standalone="no"`: the expected strings elsewhere in this file omit it,
     * and only the normalising assertion hid that mismatch.
     */
    #[Test]
    public function producesTheExactDocumentBytes(): void
    {
        // Own encoder purely to skip the name converter, so the expected bytes
        // carry the raw class short name. Author annotates its property, so one
        // type extractor suffices — no unexplained variance in a test whose
        // entire purpose is exactness.
        //
        // This pins DOMDocument's own formatting too: the declaration, the
        // two-space indent and the trailing newline all come from formatOutput.
        // If that ever changes, expect this to fail as a byte diff rather than
        // as a semantic one.
        $extractor = new PropertyInfoExtractor(
            [new ReflectionExtractor()],
            [new PhpDocExtractor()]
        );

        self::assertSame(
            "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n"
            . "<Author>\n"
            . "  <name>Jane Doe</name>\n"
            . "</Author>\n",
            (new XmlEncoder($extractor))->map(new Author())
        );
    }

    /**
     * A typed property that was never assigned is skipped like a null value.
     *
     * Reading it raises a native Error, which is outside every documented
     * guarantee of map() — neither the false return nor DOMException covers it.
     * Uninitialized typed properties are an ordinary DTO shape, so the encoder
     * has to tolerate them rather than terminate the whole mapping.
     */
    #[Test]
    public function skipsUninitializedTypedProperties(): void
    {
        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <uninitializedHost>
                    <filled>value</filled>
                </uninitializedHost>
                XML,
            (string) $this->getXmlEncoder()->map(new UninitializedHost())
        );
    }

    /**
     * A nested map() call must not pull the document out from under the outer
     * one. A custom-type closure serialising a sub-object through the same
     * encoder is the realistic way to reach this.
     */
    #[Test]
    public function survivesANestedMapCallOnTheSameInstance(): void
    {
        $encoder = $this->getXmlEncoder();

        // Registered under the builtin object key: that is the lookup this
        // encoder supports, and it makes every object property run the closure.
        $encoder->addType(
            'object',
            static fn (string $name, object $value): string => (string) $encoder->map(new Author())
        );

        $host         = new CustomTypeHost();
        $host->author = new Author();

        $outer = (string) $encoder->map($host);

        // The outer document survived: it still has its own root and parses.
        $root = $this->parseDocumentElement($outer, 'A nested map() call corrupted the outer document');

        self::assertSame('customTypeHost', $root->nodeName);

        // The inner run has to have produced something as well: asserting only
        // that the outer document survived cannot tell "both ran" apart from
        // "the outer survived and the inner returned nothing".
        //
        // The element is pinned before its text is read. A `?? ''` fallback here
        // would collapse "no author element at all" and "an empty one" into the
        // same failure message, which is the distinction under test.
        $author = $root->ownerDocument?->getElementsByTagName('author')->item(0);

        self::assertInstanceOf(DOMElement::class, $author);
        self::assertStringContainsString('<name>Jane Doe</name>', $author->textContent);
    }

    /**
     * A name converter is a documented extension point, so a converter that
     * yields an XML-invalid element name is a reachable path rather than an
     * exotic one. The exception class is asserted but not its message, which
     * comes from ext-dom and varies across PHP versions.
     */
    #[Test]
    public function throwsWhenTheConvertedNameIsNotAValidElementName(): void
    {
        $extractor = new PropertyInfoExtractor(
            [new ReflectionExtractor()],
            [new PhpDocExtractor()]
        );

        $converter = new class implements PropertyNameConverterInterface {
            /**
             * Leaves the root element name alone and produces a leading digit
             * for every property name, which is not valid for an XML element.
             *
             * @param string $name Raw class or property name
             *
             * @return string
             */
            public function convert(string $name): string
            {
                return $name === 'Author' ? $name : '1' . $name;
            }
        };

        $this->expectException(DOMException::class);

        (new XmlEncoder($extractor, $converter))->map(new Author());
    }
}
