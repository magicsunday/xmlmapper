[![Latest version](https://img.shields.io/github/v/release/magicsunday/xmlmapper?sort=semver)](https://github.com/magicsunday/xmlmapper/releases/latest)
[![License](https://img.shields.io/github/license/magicsunday/xmlmapper)](https://github.com/magicsunday/xmlmapper/blob/main/LICENSE)
[![PHPStan](https://github.com/magicsunday/xmlmapper/actions/workflows/phpstan.yml/badge.svg)](https://github.com/magicsunday/xmlmapper/actions/workflows/phpstan.yml)
[![PHPCodeSniffer](https://github.com/magicsunday/xmlmapper/actions/workflows/phpcs.yml/badge.svg)](https://github.com/magicsunday/xmlmapper/actions/workflows/phpcs.yml)
[![PHPUnit](https://github.com/magicsunday/xmlmapper/actions/workflows/phpunit.yml/badge.svg)](https://github.com/magicsunday/xmlmapper/actions/workflows/phpunit.yml)


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
vendor/bin/phpcs ./src --standard=PSR12
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/phpunit
```
