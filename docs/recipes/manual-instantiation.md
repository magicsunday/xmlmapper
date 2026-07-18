# Manual instantiation

`XmlEncoder` does not ship a factory; you wire its two collaborators yourself. This
keeps the dependency on `symfony/property-info` explicit and lets you swap in your
own extractors or name converter.

## Default wiring

A `PropertyInfoExtractor` built from a `ReflectionExtractor` (property list) and a
`PhpDocExtractor` (PHPDoc types) covers the common case:

```php
use MagicSunday\XmlEncoder;
use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

$extractor = new PropertyInfoExtractor(
    [new ReflectionExtractor()],   // list extractors: which properties exist
    [new PhpDocExtractor()]        // type extractors: each property's type
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
  skipped entirely.

  Read the redaction case literally: a `private` field is encoded with its **raw**
  value even when its public accessor masks it. Neither the visibility of the
  field nor a masking getter keeps a secret out of the output, and there is
  currently no per-property opt-out. Visibility offers no lever at all, and
  narrowing the list extractor is the only one on that axis — all-or-nothing.
  A closure registered through `addType()` can replace the value before it is
  written (see [Custom types](type-converters.md)), but it keys on the type, so
  it applies to every property of that type rather than to one.
- The **type extractors** resolve each property's type, which drives collection
  detection and the custom-type lookup key. `PhpDocExtractor` reads `@var`
  annotations such as `@var Chapter[]`; the `ReflectionExtractor` also contributes
  native property types.

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
