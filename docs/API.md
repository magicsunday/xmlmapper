# API reference

The public surface of `magicsunday/xmlmapper` is intentionally small: one encoder
class, one marker interface, the property markers, a name-converter contract and the
exception the encoder raises.

## `MagicSunday\XmlEncoder`

Encodes a PHP object graph into an XML string.

### `__construct(PropertyInfoExtractorInterface $extractor, ?PropertyNameConverterInterface $nameConverter = null)`

| Parameter        | Type                                          | Description                                                                 |
|------------------|-----------------------------------------------|-----------------------------------------------------------------------------|
| `$extractor`     | `Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface` | Resolves the list of properties and each property's type.                   |
| `$nameConverter` | `PropertyNameConverterInterface\|null`         | Optional. Converts class and property names into element names. Default `null` (raw names are used). |

See [Manual instantiation](recipes/manual-instantiation.md) for how to wire the
Symfony extractor.

### `map(XmlSerializable $instance): string|false`

Encodes `$instance` and returns the XML document as a string, or `false` if
`DOMDocument::saveXML()` fails. May throw `DOMException` for invalid element
names, and `CircularReferenceException` for a cyclic object graph.

The root element is named after the object's short class name (passed through the
name converter when one is configured). Properties with a `null` value are
skipped, as are typed properties that were never assigned and, from PHP 8.4 on,
write-only properties, which are virtual properties declared with a `set` hook and
no `get` hook. A static property is state of the class and not of the object, so it
is never encoded.

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
