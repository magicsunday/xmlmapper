<h1 align="center">XmlMapper: PHP Object to XML Mapping</h1>

<p align="center">
  Map strongly-typed PHP objects to XML using Symfony's PropertyInfo and TypeInfo components.
</p>

<!-- Row 1: CI / Quality badges -->
<p align="center">
  <a href="https://github.com/magicsunday/xmlmapper/actions/workflows/ci.yml"><img src="https://github.com/magicsunday/xmlmapper/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
</p>

<!-- Row 2: Standards / Tooling badges -->
<p align="center">
  <a href="https://phpstan.org/"><img src="https://img.shields.io/badge/PHPStan-max%20level-brightgreen.svg" alt="PHPStan Max Level"></a>
  <a href="https://phpunit.de/"><img src="https://img.shields.io/badge/PHPUnit-12%20%7C%2013-blue.svg" alt="PHPUnit 12 | 13"></a>
  <a href="https://getrector.com/"><img src="https://img.shields.io/badge/Rector-2.0-orange.svg" alt="Rector 2.0"></a>
  <a href="https://www.php-fig.org/per/coding-style/"><img src="https://img.shields.io/badge/Code%20Style-PER--CS%202.0-blue.svg" alt="PER-CS 2.0"></a>
</p>

<!-- Row 3: Compatibility badges -->
<p align="center">
  <a href="composer.json"><img src="https://img.shields.io/badge/php-8.3%20%7C%208.4%20%7C%208.5-blue" alt="PHP Version"></a>
</p>

<!-- Row 4: Project badges -->
<p align="center">
  <a href="https://github.com/magicsunday/xmlmapper/releases/latest"><img src="https://img.shields.io/github/v/release/magicsunday/xmlmapper?sort=semver" alt="Latest version"></a>
  <a href="LICENSE"><img src="https://img.shields.io/github/license/magicsunday/xmlmapper" alt="License"></a>
</p>

---

## 📌 Overview
XmlMapper is a PHP library that maps strongly-typed PHP objects (DTOs, value objects, entities) to XML using reflection and PHPDoc annotations. It leverages Symfony's PropertyInfo and TypeInfo components to derive each property's type and render a matching XML document.

| Key      | Value                                              |
|----------|----------------------------------------------------|
| Package  | `magicsunday/xmlmapper`                            |
| PHP      | `^8.3`                                             |
| Main API | `MagicSunday\XmlEncoder`                           |
| Output   | XML string (`string`); `false` only if serialization itself fails |

## ❓ What is this?
XmlMapper takes a PHP object implementing `MagicSunday\XmlSerializable` and renders it as XML, including nested objects, scalar and object collections, and custom types. Property values are routed to elements, attributes, raw text nodes or CDATA sections via a small set of annotations, and property names can be converted on the fly (e.g. snake_case to camelCase).

## 🎯 Why does this exist?
Serializing domain objects to XML usually means hand-writing `DOMDocument`/`XMLWriter` boilerplate that drifts from the underlying model. XmlMapper derives the XML structure from the object's typed properties and a few annotations, so the output follows the PHP model, with explicit hooks (attributes, CDATA, node values, custom type closures) where you need to deviate.

## 🚀 Usage

```bash
composer require magicsunday/xmlmapper
```

### Quick start

Annotate the classes you want to serialize and let them implement `XmlSerializable`:

```php
namespace App\Model;

use MagicSunday\XmlSerializable;
use MagicSunday\XmlMapper\Annotation\XmlAttribute;

final class Author implements XmlSerializable
{
    public string $name = 'Jane Doe';
}

final class Book implements XmlSerializable
{
    #[XmlAttribute]
    public string $isbn = '978-3-16-148410-0';

    public string $title = 'The Title';

    public Author $author;

    /**
     * @var string[]
     */
    public array $tags = ['php', 'xml'];
}
```

Build an encoder and map an instance:

```php
require __DIR__ . '/vendor/autoload.php';

use App\Model\Author;
use App\Model\Book;
use MagicSunday\XmlEncoder;
use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

$extractor = new PropertyInfoExtractor(
    [new ReflectionExtractor()],
    // PhpDocExtractor resolves `@var` generics such as `Chapter[]`;
    // ReflectionExtractor covers native types, so an array property without a
    // docblock is still recognised as a collection instead of being silently
    // rendered as one empty element.
    [new PhpDocExtractor(), new ReflectionExtractor()]
);

$book         = new Book();
$book->author = new Author();

$encoder = new XmlEncoder($extractor, new CamelCasePropertyNameConverter());

echo $encoder->map($book);
```

```xml
<?xml version="1.0" encoding="UTF-8" standalone="no"?>
<book isbn="978-3-16-148410-0">
  <title>The Title</title>
  <author>
    <name>Jane Doe</name>
  </author>
  <tags>php</tags>
  <tags>xml</tags>
</book>
```

The name converter is optional; without it the raw class and property names are used verbatim. For collections, annotate the property with the phpDocumentor collection type so the value type can be resolved:

```php
/** @var string[] $tags */
/** @var Chapter[] $chapters */
/** @var array<int, Author|Chapter> $members */
```

### Property markers

Each marker is applied as a native PHP attribute (shown above).

| Marker             | Effect                                                              |
|--------------------|--------------------------------------------------------------------|
| `XmlAttribute`     | Render the value as an attribute of the surrounding element.        |
| `XmlNodeValue`     | Render the value as the raw text content of the surrounding element.|
| `XmlCDataSection`  | Wrap the value in a `<![CDATA[ … ]]>` section (markup left intact). |

### Custom types

Register a closure to transform every value of a given class or builtin type (`Money::class`, `bool`, `int`, `array`, `object`, …) before it is written:

```php
$encoder->addType('bool', static fn (string $name, mixed $value): string => $value === true ? 'yes' : 'no');
```

## 📚 Documentation

* [API reference](docs/API.md)
* Recipes
  * [Manual instantiation](docs/recipes/manual-instantiation.md) — wiring the Symfony extractor and name converter
  * [Markers: attributes, node values and CDATA](docs/recipes/markers.md) — native attribute syntax
  * [Custom types](docs/recipes/type-converters.md) — transforming values with `addType()`
  * [Custom name converter](docs/recipes/custom-name-converter.md) — element naming
  * [Collections](docs/recipes/collections.md) — scalar, object, nullable and union-typed collections

## 🛠️ Development

Prerequisites:

- PHP `^8.3`
- Extensions: `dom`, `xml`
- Node.js (for the copy-paste detection gate, run via `npx`)

Install dependencies:

```bash
composer install
```

Run the mandatory quality gate:

```bash
composer ci:test
```

`ci:test` includes:

- Linting (`phplint`)
- Unit tests (`phpunit`)
- Static analysis (`phpstan`, max level)
- Refactoring dry-run (`rector --dry-run`)
- Coding standards dry-run (`php-cs-fixer --dry-run`)
- Copy-paste detection (`jscpd`)

## 🤝 Contributing

Contributions are welcome. Please run the full `composer ci:test` quality gate before submitting a pull request, and keep changes covered by tests.
