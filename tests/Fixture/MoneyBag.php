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
 * Holds a collection of a class that a custom type could be registered for, so
 * the boundary of the class-key lookup stays pinned.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class MoneyBag implements XmlSerializable
{
    /**
     * @var Money[]
     */
    public array $items;

    /**
     * Seeds two entries so the collection path is exercised.
     */
    public function __construct()
    {
        $this->items = [new Money(), new Money()];
    }
}
