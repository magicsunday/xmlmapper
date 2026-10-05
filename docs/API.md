# API reference

The public surface of `magicsunday/xmlmapper` is intentionally small: one encoder
class, one marker interface, the property markers, a name-converter contract and the
exceptions the encoder raises.

## `MagicSunday\XmlEncoder`

Encodes a PHP object graph into an XML string.

### `__construct(PropertyInfoExtractorInterface $extractor, ?PropertyNameConverterInterface $nameConverter = null, bool $strict = false)`

| Parameter        | Type                                          | Description                                                                 |
|------------------|-----------------------------------------------|-----------------------------------------------------------------------------|
| `$extractor`     | `Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface` | Resolves the list of properties and each property's type.                   |
| `$nameConverter` | `PropertyNameConverterInterface\|null`         | Optional. Converts class and property names into element names. Default `null` (raw names are used). |
| `$strict`        | `bool`                                        | Optional. When `true`, `map()` throws `UnmappableValueException` for a value it cannot map instead of dropping it. Default `false`. |

See [Manual instantiation](recipes/manual-instantiation.md) for how to wire the
Symfony extractor.

### `map(XmlSerializable $instance): string|false`

Encodes `$instance` and returns the XML document as a string, or `false` if
`DOMDocument::saveXML()` fails. May throw `DOMException` for invalid element
names, `CircularReferenceException` for a cyclic object graph,
`InvalidXmlValueException` for a value that XML 1.0 cannot carry and, from a strict
encoder, `UnmappableValueException` for a value it cannot map.

The root element is named after the object's short class name (passed through the
name converter when one is configured). Properties with a `null` value are
skipped, as are typed properties that were never assigned and write-only
properties, which are virtual properties declared with a `set` hook and no `get`
hook. A static property is state of the class and not of the object, so it
is never encoded.

A lenient encoder, which is the default, drops a value it cannot map and gives no
signal. An enum case, a closure or a resource is such a value wherever it is written as
text. These are the cases that arise from the shape of the model:

- A nested object that implements neither `XmlSerializable` nor `Stringable` becomes an
  empty element.
- An array property whose type no type extractor resolved to a collection becomes one
  empty element, which is what happens without a `@var` annotation when no type
  extractor reads native types.
- A property that carries a marker (`XmlAttribute`, `XmlNodeValue` or
  `XmlCDataSection`) and holds a collection, or an object that is not `Stringable`, is
  written as an empty value, because a marker writes one scalar.
- A collection property whose custom type closure returns something that cannot be
  iterated is left out. A closure that returns `null` leaves the property out like any
  other `null` value, in a lenient and in a strict encoder alike.

A strict encoder (`new XmlEncoder($extractor, $nameConverter, true)`) throws
`UnmappableValueException` in each of these cases instead, except for a `null` result,
with a message that names the property path and the type of the value. A `Stringable`
object is written as its text and a `null` entry of a collection stays an empty element,
in both modes. Everything else is encoded exactly as by a lenient encoder.

An object that is reached again while it is still being encoded, directly through
one of its own properties or through other objects, would never finish encoding.
`map()` stops with a `CircularReferenceException` instead. The same instance on two
different branches of the graph is no cycle and is encoded in full each time. A cycle
that runs through a type closure calling `map()` again is found as well, because a
nested `map()` call continues the run it was started from.

### `addType(string $type, Closure $closure): $this`

Registers a closure that transforms every value whose property type matches
`$type`. `$type` is either a fully qualified class name or a resolved builtin type
name (`bool`, `int`, `float`, `string`, `array`, `object`); the class name is
matched first, so `object` stays available as the catch-all for every other object
property. A class key is matched against the property's own declared type — not
through the inheritance chain, and not through a collection of that class. The
closure receives `(string $name, mixed $value)` and returns the replacement value.
Returns the encoder for chaining. See [Custom types](recipes/type-converters.md).

## `MagicSunday\XmlSerializable`

Marker interface. Every class that is passed to `map()` — and every nested object
that should be encoded recursively — must implement it. It declares no methods.

## Property markers

Each marker is applied as a **native PHP attribute**. See [Markers](recipes/markers.md).

| Marker                                        | Effect                                                              |
|-----------------------------------------------|--------------------------------------------------------------------|
| `MagicSunday\XmlMapper\Annotation\XmlAttribute`    | Render the value as an attribute of the surrounding element.        |
| `MagicSunday\XmlMapper\Annotation\XmlNodeValue`    | Render the value as the raw text content of the surrounding element.|
| `MagicSunday\XmlMapper\Annotation\XmlCDataSection` | Wrap the value in a `<![CDATA[ … ]]>` section.                      |
| `MagicSunday\XmlMapper\Annotation\XmlIgnore`       | Leave the property out of the XML; it is not read at all.           |

## `MagicSunday\XmlMapper\Converter\PropertyNameConverterInterface`

```php
public function convert(string $name): string;
```

Receives a raw class or property name and returns the element name to use.
`CamelCasePropertyNameConverter` is the bundled implementation (snake_case →
camelCase via Doctrine's inflector). See
[Custom name converter](recipes/custom-name-converter.md).

## `MagicSunday\XmlMapper\Exception\CircularReferenceException`

A `RuntimeException` that `map()` throws when an object is reached again through its
own properties. The message names the property path that closes the cycle, written
as `Root.property.property`, with the position of a collection entry in brackets
after its property, for example `Node.peer.peer` or `Tree.children[0]`. A nested
`map()` call from a type closure adds the class name of its own root as a further
segment after the property whose closure runs it, for example `Node.wrap.Wrap.next`.

## `MagicSunday\XmlMapper\Exception\UnmappableValueException`

A `RuntimeException` that a strict encoder throws when it meets a value it cannot map,
see the cases listed under `map()`. The message names the property path in the
notation of `CircularReferenceException` and the type of the value, for example
`Catalog.tags` and `array`. A lenient encoder never throws it.

## `MagicSunday\XmlMapper\Exception\InvalidXmlValueException`

A `RuntimeException` that `map()` throws when a value cannot be written into an XML 1.0
document, because the document would not parse. That is the case for a character below
the space other than tab, line feed and carriage return, for U+FFFE and U+FFFF, and for
a string that is not valid UTF-8. The check covers every place a value is written
(element text, attribute, CDATA section, raw node text and the entries of a collection)
and the value a type converter returns. Nothing is stripped or replaced.

The message names the property path in the notation of `CircularReferenceException`,
for example `Book.title`. For a character it also gives the bytes of the character and
its byte offset within the value.
