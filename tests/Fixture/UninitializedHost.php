<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use MagicSunday\XmlSerializable;

/**
 * Declares a typed property that is never assigned — an ordinary shape for a
 * DTO whose optional part was not filled in.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class UninitializedHost implements XmlSerializable
{
    /**
     * @var string
     */
    public string $filled = 'value';

    /**
     * Typed, never assigned: reading it raises a native Error.
     *
     * @var string
     */
    public string $never;
}
