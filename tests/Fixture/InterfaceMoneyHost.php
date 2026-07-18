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
 * Declares its property as the interface, so the registered interface key and
 * the declared type are the same name.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class InterfaceMoneyHost implements XmlSerializable
{
    /**
     * @var MoneyLike
     */
    public MoneyLike $amount;

    /**
     * Seeds the property with a concrete implementation.
     */
    public function __construct()
    {
        $this->amount = new InterfaceMoney();
    }
}
