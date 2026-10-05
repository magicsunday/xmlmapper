# Manual instantiation

`XmlEncoder` does not ship a factory; you wire its two collaborators yourself. This
keeps the dependency on `symfony/property-info` explicit and lets you swap in your
own extractors or name converter.

## Default wiring

A `PropertyInfoExtractor` built from a `ReflectionExtractor` for the property list
and **both** extractors for the types covers the common case:

```php
use MagicSunday\XmlEncoder;
use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

$extractor = new PropertyInfoExtractor(
    [new ReflectionExtractor()],                      // list extractors: which properties exist
    [new PhpDocExtractor(), new ReflectionExtractor()] // type extractors: each property's type
);

$encoder = new XmlEncoder($extractor, new CamelCasePropertyNameConverter());
```

- The **list extractors** decide which properties are encoded. `ReflectionExtractor`
  reports everything reachable through a public accessor, which is wider than
  "public properties": a `private` field with a public getter is reported and
  therefore encoded. If that is not wanted, narrow the list extractor rather
  than relying on visibility.
- Values are read **as fields**, not through the accessor. A getter that
  formats, rounds or redacts its value therefore has no effect on the output,
  and a purely virtual property — an accessor with no backing field — is
  skipped entirely. A property declared with property hooks is a real
  property and is read like any other, through its `get` hook where it has one.
  The exception is a write-only one, which has a `set` hook and no `get` hook and
  is skipped.

  Read the redaction case literally: a `private` field is encoded with its **raw**
  value even when its public accessor masks it. Neither the visibility of the
  field nor a masking getter keeps a secret out of the output. Mark such a
  property with `#[XmlIgnore]` (see [Markers](markers.md)) to keep it out. That
  works per property and skips the property before it is read. Two other levers
  exist. `ReflectionExtractor` has no per-property switch, but a custom list
  extractor may leave out selected properties. A closure registered through
  `addType()` is chosen by type and receives the property name, so it can replace
  the value of a single property before it is written (see
  [Custom types](type-converters.md)), but it still runs for every property of
  that type.
- The **type extractors** resolve each property's type, which drives collection
  detection and the custom-type lookup key. `PhpDocExtractor` reads `@var`
  annotations such as `@var Chapter[]`; the `ReflectionExtractor` also contributes
  native property types. Listing both matters: with only `PhpDocExtractor`, an
  array property that carries no `@var` annotation resolves to no type at all,
  falls back to `string`, and is then a value no encoder can map. A strict encoder, the
  default, refuses the whole property, and a lenient one renders a single empty element
  and loses the entries without any error.

  The same change also moves the **custom-type lookup key**. A natively typed
  property that used to resolve to no type fell back to `string` and matched a
  `string` catch-all closure; once a native-type extractor is listed it resolves
  to `array`, `int` or an object type and no longer does. Re-check any `string`
  or `object` catch-all registered through `addType()` after adding one.

## Without a name converter

Passing only the extractor uses the raw class and property names verbatim:

```php
$encoder = new XmlEncoder($extractor);
```

For a class `Person` with a `home_town` property this yields:

```xml
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<Person>
  <firstName>John</firstName>
  <home_town>Berlin</home_town>
</Person>
```

With `CamelCasePropertyNameConverter` the same object becomes `<person>` /
`<homeTown>`. See [Custom name converter](custom-name-converter.md) to plug in your
own naming scheme.
