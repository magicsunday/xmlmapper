# Custom name converter

The optional second constructor argument controls how class and property names
become element names. It accepts any `PropertyNameConverterInterface`:

```php
namespace MagicSunday\XmlMapper\Converter;

interface PropertyNameConverterInterface
{
    public function convert(string $name): string;
}
```

`convert()` is called once for the root element (the object's short class name) and
once per property name.

## Bundled converter

`CamelCasePropertyNameConverter` turns snake_case into camelCase using Symfony's
inflector, so `home_town` becomes `homeTown` and a class `Person` becomes `person`.

```php
use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;

$encoder = new XmlEncoder($extractor, new CamelCasePropertyNameConverter());
```

## Writing your own

Implement the interface to enforce a different convention. For example, a converter
that kebab-cases names:

```php
use MagicSunday\XmlMapper\Converter\PropertyNameConverterInterface;

final class KebabCasePropertyNameConverter implements PropertyNameConverterInterface
{
    public function convert(string $name): string
    {
        return strtolower(
            preg_replace('/(?<!^)[A-Z]/', '-$0', $name) ?? $name
        );
    }
}
```

```php
$encoder = new XmlEncoder($extractor, new KebabCasePropertyNameConverter());
```

A property `homeTown` is then emitted as `<home-town>`.

## No converter

Omit the argument to keep the raw names (see
[Manual instantiation](manual-instantiation.md)):

```php
$encoder = new XmlEncoder($extractor);
```
