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
 * Declares its property as a subclass, so a converter registered for the parent
 * class can be shown not to apply.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class SpecialMoneyHost implements XmlSerializable
{
    /**
     * @var SpecialMoney
     */
    public SpecialMoney $amount;

    /**
     * Seeds the subclass-typed property.
     */
    public function __construct()
    {
        $this->amount = new SpecialMoney();
    }
}
