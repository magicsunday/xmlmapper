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
 * Holds the same child instance several times without any cycle, which is a valid
 * graph the encoder has to render in full.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class SharedChildHost implements XmlSerializable
{
    /**
     * @var Author|null
     */
    public ?Author $first = null;

    /**
     * @var Author|null
     */
    public ?Author $second = null;

    /**
     * The same instance again, inside a collection.
     *
     * @var Author[]
     */
    public array $others = [];
}
