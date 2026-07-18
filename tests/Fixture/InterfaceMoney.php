<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

/**
 * Implements the interface a custom type is registered under, while the host
 * declares the property as the interface rather than as this class.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class InterfaceMoney implements MoneyLike
{
    /**
     * @var int
     */
    public int $cents = 1250;
}
