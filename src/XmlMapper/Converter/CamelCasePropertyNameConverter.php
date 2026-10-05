<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\XmlMapper\Converter;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;

/**
 * This name converter converts a class or property name into a camelized name.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
final readonly class CamelCasePropertyNameConverter implements PropertyNameConverterInterface
{
    /**
     * The inflector that performs the camel-case conversion.
     */
    private Inflector $inflector;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    /**
     * Converts the specified class or property name to camel case.
     *
     * @param string $name The class or property name, where underscores, hyphens and spaces separate words
     *
     * @return string The name in camel case
     */
    public function convert(string $name): string
    {
        return $this->inflector->camelize($name);
    }
}
