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
 * This name converter converts a property name into a camelized property name.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class CamelCasePropertyNameConverter implements PropertyNameConverterInterface
{
    /**
     * @var Inflector
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
     * Converts the specified property name to another format.
     *
     * @param string $name
     *
     * @return string
     */
    public function convert(string $name): string
    {
        return $this->inflector->camelize($name);
    }
}
