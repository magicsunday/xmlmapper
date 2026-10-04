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
use Exception;
use LogicException;
use MagicSunday\Test\Fixture\Author;
use MagicSunday\Test\Fixture\BodyHost;
use MagicSunday\Test\Fixture\Book;
use MagicSunday\Test\Fixture\Chapter;
use MagicSunday\Test\Fixture\Comment;
use MagicSunday\Test\Fixture\CustomTypeHost;
use MagicSunday\Test\Fixture\CycleAfterNestedMapHost;
use MagicSunday\Test\Fixture\CyclicCollectionNode;
use MagicSunday\Test\Fixture\CyclicNode;
use MagicSunday\Test\Fixture\EscapeCDataHost;
use MagicSunday\Test\Fixture\EscapeHost;
use MagicSunday\Test\Fixture\EscapeMarkerHost;
use MagicSunday\Test\Fixture\IgnoreHost;
use MagicSunday\Test\Fixture\IgnoreRedeclaredHost;
use MagicSunday\Test\Fixture\IgnoreRepeatedHost;
use MagicSunday\Test\Fixture\InterfaceMoneyHost;
use MagicSunday\Test\Fixture\IteratorCollectionHost;
use MagicSunday\Test\Fixture\Money;
use MagicSunday\Test\Fixture\MoneyBag;
use MagicSunday\Test\Fixture\MoneyHost;
use MagicSunday\Test\Fixture\MoneyLike;
use MagicSunday\Test\Fixture\NativeCData;
use MagicSunday\Test\Fixture\NativeMarkers;
use MagicSunday\Test\Fixture\NativeWithForeignAttribute;
use MagicSunday\Test\Fixture\NativeWithForeignDocblock;
use MagicSunday\Test\Fixture\NestedMapStateHost;
use MagicSunday\Test\Fixture\Person;
use MagicSunday\Test\Fixture\PlainArrayHost;
use MagicSunday\Test\Fixture\PlainBody;
use MagicSunday\Test\Fixture\Price;
use MagicSunday\Test\Fixture\SerializableMoney;
use MagicSunday\Test\Fixture\SerializableMoneyBag;
use MagicSunday\Test\Fixture\SharedChildHost;
use MagicSunday\Test\Fixture\SpecialMoney;
use MagicSunday\Test\Fixture\SpecialMoneyHost;
use MagicSunday\Test\Fixture\ThrowingKeyIterator;
use MagicSunday\Test\Fixture\UninitializedHost;
use MagicSunday\Test\Fixture\UnionObjectHost;
use MagicSunday\Test\Fixture\UnionProperty;
use MagicSunday\Test\Fixture\UnmarkedNested;
use MagicSunday\Test\Fixture\VisibilityHost;
use MagicSunday\XmlEncoder;
use MagicSunday\XmlMapper\Annotation\XmlAttribute;
use MagicSunday\XmlMapper\Annotation\XmlCDataSection;
use MagicSunday\XmlMapper\Annotation\XmlIgnore;
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;
use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;
use MagicSunday\XmlMapper\Converter\PropertyNameConverterInterface;
use MagicSunday\XmlMapper\Exception\CircularReferenceException;
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
#[UsesClass(XmlIgnore::class)]
#[UsesClass(CircularReferenceException::class)]
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
     * An array property without a `@var` annotation is recognised as a
     * collection as long as a type extractor reads native types.
     *
     * With only PhpDocExtractor the type resolves to nothing, falls back to
     * string, and the entries are dropped into a single empty element without
     * any error — which is why the documented configuration lists both.
     */
    #[Test]
    public function encodesAnArrayPropertyWithoutADocblock(): void
    {
        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <plainArrayHost>
                    <tags>a</tags>
                    <tags>b</tags>
                </plainArrayHost>
                XML,
            (string) $this->getXmlEncoder()->map(new PlainArrayHost())
        );
    }

    /**
     * Only PhpDocExtractor: the same property loses its entries. Pinned so the
     * cost of dropping ReflectionExtractor from the type extractors stays
     * visible instead of surfacing as missing data in production.
     */
    #[Test]
    public function losesArrayEntriesWhenNoTypeExtractorReadsNativeTypes(): void
    {
        $extractor = new PropertyInfoExtractor(
            [new ReflectionExtractor()],
            [new PhpDocExtractor()]
        );

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <PlainArrayHost>
                    <tags/>
                </PlainArrayHost>
                XML,
            (string) (new XmlEncoder($extractor))->map(new PlainArrayHost())
        );
    }

    /**
     * A nested object that does not implement XmlSerializable renders as an
     * empty element rather than raising anything. Pinned because it is the most
     * likely mistake when adding a node type, and the symptom points nowhere.
     */
    #[Test]
    public function rendersANestedObjectWithoutTheMarkerInterfaceAsEmpty(): void
    {
        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <unmarkedNested>
                    <inner/>
                </unmarkedNested>
                XML,
            (string) $this->getXmlEncoder()->map(new UnmarkedNested())
        );
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

        // Registered under the builtin object key rather than a class name: the
        // catch-all is what makes every object property run the closure, which is
        // what this test needs.
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
     * An object that reaches itself through one of its own properties never
     * finishes encoding. The encoder has to stop with a library exception that
     * names the property path closing the cycle, not run into the execution time
     * limit.
     */
    #[Test]
    public function throwsOnAnObjectThatReferencesItself(): void
    {
        $node       = new CyclicNode();
        $node->peer = $node;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote('"CyclicNode.peer"', '/') . '/');

        $this->getXmlEncoder()->map($node);
    }

    /**
     * Two objects that point at each other close the cycle one object later, and
     * the path names both steps.
     */
    #[Test]
    public function throwsOnTwoObjectsThatReferenceEachOther(): void
    {
        $first        = new CyclicNode();
        $second       = new CyclicNode();
        $first->peer  = $second;
        $second->peer = $first;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote('"CyclicNode.peer.peer"', '/') . '/');

        $this->getXmlEncoder()->map($first);
    }

    /**
     * A cycle closed through an entry of a collection is found as well, and the
     * path carries the entry index.
     */
    #[Test]
    public function throwsOnACycleThroughACollectionEntry(): void
    {
        $node             = new CyclicCollectionNode();
        $node->children[] = $node;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote('"CyclicCollectionNode.children[0]"', '/') . '/');

        $this->getXmlEncoder()->map($node);
    }

    /**
     * The same instance reached on several branches is not a cycle. Only an
     * object that is still being encoded further up the same path counts, so a
     * seen-before set instead of the active path would reject this valid graph.
     */
    #[Test]
    public function encodesASharedChildThatIsNoCycle(): void
    {
        $author       = new Author();
        $host         = new SharedChildHost();
        $host->first  = $author;
        $host->second = $author;
        $host->others = [$author, $author];

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <sharedChildHost>
                    <first><name>Jane Doe</name></first>
                    <second><name>Jane Doe</name></second>
                    <others><name>Jane Doe</name></others>
                    <others><name>Jane Doe</name></others>
                </sharedChildHost>
                XML,
            (string) $this->getXmlEncoder()->map($host)
        );
    }

    /**
     * A cycle that runs through a type converter calling map() again is found as
     * well. The nested call is part of the outer encode, so it keeps the set of
     * objects being encoded and extends the path with its own root. A nested call
     * that started with an empty set would never meet the object it was reached
     * from, and the recursion would not end.
     */
    #[Test]
    public function throwsOnACycleThatRunsThroughANestedMapCall(): void
    {
        $encoder = $this->getXmlEncoder();

        $encoder->addType(
            CyclicNode::class,
            static fn (string $name, CyclicNode $value): string => (string) $encoder->map($value)
        );

        $first        = new CyclicNode();
        $second       = new CyclicNode();
        $first->peer  = $second;
        $second->peer = $first;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote('"CyclicNode.peer.CyclicNode.peer.CyclicNode"', '/') . '/');

        $encoder->map($first);
    }

    /**
     * An object that is encoded through nested map() calls on several branches
     * is no cycle either, and every branch is encoded in full.
     */
    #[Test]
    public function encodesASharedChildThroughNestedMapCalls(): void
    {
        $encoder = $this->getXmlEncoder();

        $encoder->addType(
            Author::class,
            static fn (string $name, Author $value): string => (string) $encoder->map($value)
        );

        $author       = new Author();
        $host         = new SharedChildHost();
        $host->first  = $author;
        $host->second = $author;
        $host->others = [$author, $author];

        self::assertSame(
            4,
            substr_count((string) $encoder->map($host), 'Jane Doe'),
            'The same author has to be encoded in full for first, second and both list entries.'
        );
    }

    /**
     * A nested call that fails and is caught inside the converter hands the outer
     * state back as it was. Whatever the failed call left on the set and the path
     * would otherwise show up in the path of the next cycle the outer run meets.
     */
    #[Test]
    public function keepsTheOuterStateWhenANestedMapCallThrows(): void
    {
        $encoder = $this->getXmlEncoder();

        $cyclic       = new CyclicNode();
        $cyclic->peer = $cyclic;

        $encoder->addType(
            Author::class,
            static function (string $name, Author $value) use ($encoder, $cyclic): string {
                try {
                    return (string) $encoder->map($cyclic);
                } catch (CircularReferenceException) {
                    return 'caught';
                }
            }
        );

        $host         = new CycleAfterNestedMapHost();
        $host->author = new Author();
        $host->self   = $host;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote('"CycleAfterNestedMapHost.self"', '/') . '/');

        $encoder->map($host);
    }

    /**
     * A collection held as an iterator is walked without asking it for its keys.
     * The recipes allow such collections, and the path bookkeeping must not add a
     * call to them that they were never subject to.
     */
    #[Test]
    public function encodesAnIteratorCollectionWithoutReadingItsKeys(): void
    {
        $host          = new IteratorCollectionHost();
        $host->authors = new ThrowingKeyIterator([new Author(), new Author()]);

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <iteratorCollectionHost>
                    <authors><name>Jane Doe</name></authors>
                    <authors><name>Jane Doe</name></authors>
                </iteratorCollectionHost>
                XML,
            (string) $this->getXmlEncoder()->map($host)
        );
    }

    /**
     * A collection leaves nothing of its entry positions on the path once it is
     * encoded. The cycle that comes after it is reported at its own short path,
     * where a position left behind would show up in the message.
     */
    #[Test]
    public function leavesNoCollectionPositionOnThePathAfterTheCollection(): void
    {
        $node           = new CyclicCollectionNode();
        $node->children = [new CyclicCollectionNode(), new CyclicCollectionNode()];
        $node->parent   = $node;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote('"CyclicCollectionNode.parent"', '/') . '/');

        $this->getXmlEncoder()->map($node);
    }

    /**
     * A nested map() call that fails and is caught inside the converter hands the
     * set of objects being encoded back as well. The node it started from has to
     * be encodable again afterwards, as a plain property of the outer run. If the
     * failed call left it behind as still being encoded, the outer run would report
     * a cycle that does not exist.
     */
    #[Test]
    public function encodesTheNodeAgainAfterAFailedNestedMapCall(): void
    {
        $encoder = $this->getXmlEncoder();

        $leaf         = new CyclicNode();
        $parent       = new CyclicNode();
        $parent->peer = $leaf;

        $failures = 0;

        $encoder->addType(
            CyclicNode::class,
            static function (string $name, ?CyclicNode $value) use ($leaf, &$failures): ?CyclicNode {
                if (($value === $leaf) && (++$failures === 1)) {
                    throw new LogicException('The first conversion of the leaf fails.');
                }

                return $value;
            }
        );

        $encoder->addType(
            Author::class,
            static function (string $name, Author $value) use ($encoder, $parent): string {
                try {
                    return (string) $encoder->map($parent);
                } catch (Exception) {
                    return 'caught';
                }
            }
        );

        $host         = new NestedMapStateHost();
        $host->author = new Author();
        $host->shared = $parent;

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <nestedMapStateHost>
                    <author>caught</author>
                    <shared>
                        <peer><label>x</label></peer>
                        <label>x</label>
                    </shared>
                </nestedMapStateHost>
                XML,
            (string) $encoder->map($host)
        );
    }

    /**
     * A nested map() call from a type converter extends the path of the outer run
     * and hands it back as it was. The cycle that comes after the converter ran
     * is therefore still found at its own, short path. A nested call that left
     * its own steps on the path would make the path in the message longer.
     */
    #[Test]
    public function keepsTheOuterPathWhenANestedMapCallRuns(): void
    {
        $encoder = $this->getXmlEncoder();

        $encoder->addType(
            Author::class,
            static fn (string $name, Author $value): string => (string) $encoder->map(new Author())
        );

        $host         = new CycleAfterNestedMapHost();
        $host->author = new Author();
        $host->self   = $host;

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote('"CycleAfterNestedMapHost.self"', '/') . '/');

        $encoder->map($host);
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

    /**
     * The interface half of the same rule.
     *
     * Registering under an interface is not a special case: it fires exactly
     * when the property is declared as that interface, because the lookup
     * compares names rather than walking the hierarchy. Pinned because it is
     * the one branch of the rule that prose alone was holding, and because the
     * failure is silent — a miss yields an empty element, not an error.
     */
    #[Test]
    public function appliesAClassKeyRegisteredUnderAnInterfaceToAnInterfaceTypedProperty(): void
    {
        $result = (string) $this->getXmlEncoder()
            ->addType(MoneyLike::class, static fn (string $name, MoneyLike $value): string => 'iface')
            ->map(new InterfaceMoneyHost());

        self::assertStringContainsString('<amount>iface</amount>', $result);
    }

    /**
     * A custom type registered under a class name applies to properties of that
     * class only.
     *
     * The lookup key used to be the builtin type name, which collapses every
     * object to "object" — so the obvious use case (a converter for one value
     * object) could not be expressed without also hijacking every other object
     * property in the model.
     */
    #[Test]
    public function appliesACustomTypeRegisteredUnderAClassName(): void
    {
        $xml = $this->getXmlEncoder()
            ->addType(Money::class, static fn (string $name, Money $value): string => '12.50 EUR')
            ->map(new MoneyHost());

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <moneyHost>
                    <amount>12.50 EUR</amount>
                    <author>
                        <name>Jane Doe</name>
                    </author>
                </moneyHost>
                XML,
            (string) $xml
        );
    }

    /**
     * The registered name is compared against the property's declared type —
     * the hierarchy is not walked, and a collection is not unwrapped.
     *
     * Pinned because both are the obvious next thing a reader tries after the
     * class-specific registration works, and both fail silently: the entry
     * falls through to the scalar path, which yields an empty element.
     */
    #[Test]
    public function doesNotApplyAClassKeyToACollectionOfThatClass(): void
    {
        $host = new MoneyBag();

        $xml = $this->getXmlEncoder()
            ->addType(Money::class, static fn (string $name, array $value): string => 'converted')
            ->map($host);

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <moneyBag>
                    <items/>
                    <items/>
                </moneyBag>
                XML,
            (string) $xml
        );
    }

    /**
     * The class key is not resolved through the inheritance chain either: a
     * converter registered for the parent class does not fire for a property
     * declared as a subclass, and the entry then renders as an empty element.
     */
    #[Test]
    public function doesNotApplyAClassKeyToASubclassProperty(): void
    {
        $xml = $this->getXmlEncoder()
            ->addType(Money::class, static fn (string $name, SpecialMoney $value): string => 'converted')
            ->map(new SpecialMoneyHost());

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <specialMoneyHost>
                    <amount/>
                </specialMoneyHost>
                XML,
            (string) $xml
        );
    }

    /**
     * The encoding boundary is drawn by the list extractor, not by the
     * visibility of the backing field, and values are then read as fields.
     *
     * Both halves matter and they disagree in one direction: a private field
     * with a public accessor IS reported by ReflectionExtractor and therefore
     * encoded, while a purely virtual accessor has no field to read and is
     * dropped. Accessor transformations are consequently not applied.
     */
    #[Test]
    public function encodesWhatTheExtractorReportsAndReadsItAsAField(): void
    {
        $xml = (string) $this->getXmlEncoder()->map(new VisibilityHost());

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <visibilityHost>
                    <visible>public</visible>
                    <hidden>private-with-getter</hidden>
                    <transformed>from-field</transformed>
                </visibilityHost>
                XML,
            $xml
        );

        self::assertStringNotContainsString('computed', $xml);
    }

    /**
     * The ignore marker takes a property out of the output whatever shape it has:
     * a plain public property, one that also carries another marker, a private
     * field the extractor reports through its accessor, and a whole collection.
     *
     * The only property without the marker stays, so the exclusion is per
     * property and not a blanket drop. Compared as parsed XML plus a literal
     * absence check, because the leaked value is exactly what must not appear.
     */
    #[Test]
    public function omitsPropertiesMarkedAsIgnored(): void
    {
        $xml = (string) $this->getXmlEncoder()->map(new IgnoreHost());

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <ignoreHost>
                    <kept>kept</kept>
                </ignoreHost>
                XML,
            $xml
        );

        self::assertStringNotContainsString('token', $xml);
        self::assertStringNotContainsString('skipped', $xml);
        self::assertStringNotContainsString('Intro', $xml);
    }

    /**
     * An ignored property is not touched at all, so a custom type converter
     * registered for its type never sees it. Otherwise a converter with a side
     * effect, or one that throws on the hidden value, would still run for data
     * the author asked to keep out.
     */
    #[Test]
    public function doesNotPassAnIgnoredPropertyToACustomTypeConverter(): void
    {
        $seen = [];

        $this->getXmlEncoder()
            ->addType(
                'string',
                static function (string $name, string $value) use (&$seen): string {
                    $seen[] = $name;

                    return $value;
                }
            )
            ->map(new IgnoreHost());

        self::assertSame(['kept'], $seen);
    }

    /**
     * The marker is read from the property declaration of the concrete class, so
     * a subclass that redeclares an ignored property has to repeat it.
     *
     * Without the marker on the redeclaration the property is encoded again, and
     * that is pinned on purpose because the documentation states it. The second
     * assertion is a characterization: it does not fail on the code before the
     * marker existed, so it documents the boundary and is not a regression guard.
     */
    #[Test]
    public function requiresTheIgnoreMarkerToBeRepeatedOnARedeclaredProperty(): void
    {
        $encoder = $this->getXmlEncoder();

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <ignoreRepeatedHost/>
                XML,
            (string) $encoder->map(new IgnoreRepeatedHost())
        );

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <ignoreRedeclaredHost>
                    <token>child-secret</token>
                </ignoreRedeclaredHost>
                XML,
            (string) $encoder->map(new IgnoreRedeclaredHost())
        );
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
     * The builtin key keeps working as the catch-all, and the class-specific
     * registration wins over it when both are present.
     */
    #[Test]
    public function prefersTheClassSpecificCustomTypeOverTheBuiltinCatchAll(): void
    {
        $xml = $this->getXmlEncoder()
            ->addType('object', static fn (string $name, object $value): string => 'any-object')
            ->addType(Money::class, static fn (string $name, Money $value): string => 'money')
            ->map(new MoneyHost());

        self::assertXmlStringEqualsXmlString(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <moneyHost>
                    <amount>money</amount>
                    <author>any-object</author>
                </moneyHost>
                XML,
            (string) $xml
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
     * The costly half of the collection boundary.
     *
     * A missed class key is harmless only while the class does not implement
     * the marker interface — then the entry renders empty. Implement it, and the
     * encoder walks the object instead, so a closure registered to redact or
     * format a value silently emits the untouched contents. That is fail-open,
     * and it is the shape a domain value object most plausibly has.
     */
    #[Test]
    public function walksTheEntriesWhenAMissedClassKeyMeetsTheMarkerInterface(): void
    {
        $encoder = $this->getXmlEncoder()
            ->addType(SerializableMoney::class, static fn (string $name, SerializableMoney $value): string => '[redacted]');

        $result = (string) $encoder->map(new SerializableMoneyBag());

        // The closure did not fire ...
        self::assertStringNotContainsString('[redacted]', $result);

        // ... and the value it was meant to replace is in the output instead.
        self::assertStringContainsString('1250', $result);
    }

    /**
     * The repeated call produces the expected document, not merely the same one
     * twice.
     *
     * mapIsRepeatableOnTheSameInstance() compares the two runs against each
     * other and parses the result back, so an encoder that produced identical
     * but wrong output on both calls would satisfy it. Other tests do pin the
     * content, but only for a single call — this is the one place where the
     * repeated path and the expected content are asserted together.
     *
     * Adapted from a fix proposed by @Dodothereal in PR #45, which arrived
     * before the repeatability fix landed and carried this assertion.
     */
    #[Test]
    public function repeatedCallsProduceTheExpectedDocumentNotJustTheSameOne(): void
    {
        $encoder  = $this->getXmlEncoder();
        $person   = new Person();
        $expected = <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <person>
                <firstName>John</firstName>
                <homeTown>Berlin</homeTown>
                <age>42</age>
                <active>1</active>
            </person>
            XML;

        self::assertXmlStringEqualsXmlString($expected, (string) $encoder->map($person));
        self::assertXmlStringEqualsXmlString($expected, (string) $encoder->map($person));
    }
}
