# Markers: attributes, node values, CDATA and ignored properties

By default every property becomes a child element. These markers change that for an
individual property. Each marker is written as a **native PHP attribute**.

| Marker             | Syntax               | Effect                                            |
|--------------------|----------------------|---------------------------------------------------|
| `XmlAttribute`     | `#[XmlAttribute]`    | Value becomes an attribute of the element.        |
| `XmlNodeValue`     | `#[XmlNodeValue]`    | Value becomes the element's raw text content.     |
| `XmlCDataSection`  | `#[XmlCDataSection]` | Value is wrapped in `<![CDATA[ … ]]>`.            |
| `XmlIgnore`        | `#[XmlIgnore]`       | Property is left out of the XML entirely.         |

All of them target a property.

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

## Ignored properties

`XmlIgnore` keeps a property out of the output. The encoder neither reads the
property nor passes it to a custom type converter, and the marker wins over any
other marker on the same property.

```php
use MagicSunday\XmlSerializable;
use MagicSunday\XmlMapper\Annotation\XmlIgnore;

final class Account implements XmlSerializable
{
    public string $name = 'jane';

    #[XmlIgnore]
    private string $token = 'secret';

    public function getToken(): string
    {
        return $this->token;
    }
}
```

```xml
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<account>
  <name>jane</name>
</account>
```

Without the marker the private field would be encoded, because the list extractor
reports it through its public getter. See
[Manual instantiation](manual-instantiation.md) for how the extractor decides.

## Notes

- Apart from `XmlIgnore`, a property may carry at most one of these markers; the
  encoder checks them in the order attribute → CDATA → node value and uses the first
  that matches. `XmlIgnore` is checked before all of them.
- Markers are read per property by reflection, so they work on any public property
  of an `XmlSerializable` class, including nested objects.
