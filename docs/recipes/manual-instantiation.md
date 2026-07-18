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
  exposes public properties.
- The **type extractors** resolve each property's type, which drives collection
  detection and the custom-type lookup key. `PhpDocExtractor` reads `@var`
  annotations such as `@var Chapter[]`; the `ReflectionExtractor` also contributes
  native property types. Listing both matters: with only `PhpDocExtractor`, an
  array property that carries no `@var` annotation resolves to no type at all,
  falls back to `string`, and is then rendered as a single empty element — its
  entries are dropped without any error.

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
