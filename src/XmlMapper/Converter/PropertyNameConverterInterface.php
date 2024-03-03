<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\XmlMapper\Converter;

/**
 * PropertyNameConverterInterface.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
interface PropertyNameConverterInterface
{
    /**
     * Converts the specified property name to another format.
     *
     * @param string $name
     *
     * @return string
     */
    public function convert(string $name): string;
}
