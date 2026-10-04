<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use MagicSunday\XmlMapper\Converter\PropertyNameConverterInterface;

/**
 * A name converter that prefixes every name but one, so a test can tell the name
 * a property has in the object from the name its element gets. The prefixed name
 * is still a valid XML element name.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class PrefixingPropertyNameConverter implements PropertyNameConverterInterface
{
    /**
     * Constructor.
     *
     * @param string $unchangedName The one name that is returned as it is, normally the root class name
     */
    public function __construct(private readonly string $unchangedName)
    {
    }

    /**
     * Prefixes the specified name unless it is the one that stays as it is.
     *
     * @param string $name
     *
     * @return string
     */
    public function convert(string $name): string
    {
        if ($name === $this->unchangedName) {
            return $name;
        }

        return 'x' . $name;
    }
}
