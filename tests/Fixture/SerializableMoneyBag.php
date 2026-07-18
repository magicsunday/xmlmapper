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
 * Holds a collection of a marker-implementing class, so the fail-open half of
 * the class-key boundary stays pinned.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class SerializableMoneyBag implements XmlSerializable
{
    /**
     * @var SerializableMoney[]
     */
    public array $items;

    /**
     * Seeds a single entry; one is enough to show the closure did not fire.
     */
    public function __construct()
    {
        $this->items = [new SerializableMoney()];
    }
}
