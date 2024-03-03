[![Latest version](https://img.shields.io/github/v/release/magicsunday/xmlmapper?sort=semver)](https://github.com/magicsunday/xmlmapper/releases/latest)
[![License](https://img.shields.io/github/license/magicsunday/xmlmapper)](https://github.com/magicsunday/xmlmapper/blob/main/LICENSE)
[![CI](https://github.com/magicsunday/xmlmapper/actions/workflows/ci.yml/badge.svg)](https://github.com/magicsunday/xmlmapper/actions/workflows/ci.yml)

# XmlMapper
This module provides a mapper to map PHP classes to XML utilizing Symfony's property info and access packages.

## Installation

### Using Composer
To install using [composer](https://getcomposer.org/), just run the following command from the command line.

```bash
composer require magicsunday/xmlmapper
```

To remove the module run:
```bash
composer remove magicsunday/xmlmapper
```


## Development

### Testing
```bash
composer update
composer ci:cgl
composer ci:test
composer ci:test:php:phplint
composer ci:test:php:phpstan
composer ci:test:php:rector
composer ci:test:php:unit
```
