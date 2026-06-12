# Markers: attributes, node values and CDATA

By default every property becomes a child element. Three markers change that for an
individual property. Each marker is written as a **native PHP attribute**.

| Marker             | Syntax               | Effect                                            |
|--------------------|----------------------|---------------------------------------------------|
| `XmlAttribute`     | `#[XmlAttribute]`    | Value becomes an attribute of the element.        |
| `XmlNodeValue`     | `#[XmlNodeValue]`    | Value becomes the element's raw text content.     |
| `XmlCDataSection`  | `#[XmlCDataSection]` | Value is wrapped in `<![CDATA[ … ]]>`.            |

All three target a property.

> The example outputs below assume an encoder wired with the
> `CamelCasePropertyNameConverter` (as in the [quick start](../../README.md)), which
> is why the root and element names are lower/camelCased. Without a name converter
> the raw class name is used (e.g. `<Price>` instead of `<price>`).

## Attribute and node value

```php
use MagicSunday\XmlSerializable;
use MagicSunday\XmlMapper\Annotation\XmlAttribute;
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;

final class Price implements XmlSerializable
{
    #[XmlAttribute]
    public string $currency = 'EUR';

    #[XmlNodeValue]
    public string $amount = '42.00';
}
```

```xml
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<price currency="EUR">42.00</price>
```

## CDATA section

`XmlCDataSection` writes the value inside a CDATA block, so embedded markup is kept
verbatim rather than being escaped:

```php
use MagicSunday\XmlSerializable;
use MagicSunday\XmlMapper\Annotation\XmlCDataSection;

final class Comment implements XmlSerializable
{
    #[XmlCDataSection]
    public string $body = '<b>hi</b>';
}
```

```xml
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<comment><![CDATA[<b>hi</b>]]></comment>
```

Without the marker the same property would be escaped to
`<body>&lt;b&gt;hi&lt;/b&gt;</body>`.

## Notes

- A property may carry at most one of these markers; the encoder checks them in the
  order attribute → CDATA → node value and uses the first that matches.
- Markers are read per property by reflection, so they work on any public property
  of an `XmlSerializable` class, including nested objects.
