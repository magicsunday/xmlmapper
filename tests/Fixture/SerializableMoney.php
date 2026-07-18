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
 * Like the plain value object, but it implements the marker interface. That
 * single difference decides what a missed class key costs: without the marker a
 * missed entry renders empty, with it the encoder walks the object instead.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class SerializableMoney implements XmlSerializable
{
    /**
     * @var int
     */
    public int $cents = 1250;
}
