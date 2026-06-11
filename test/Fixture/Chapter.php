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
 * A fixture used as the value type of an object collection.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class Chapter implements XmlSerializable
{
    /**
     * @var string
     */
    public string $heading;

    /**
     * @var int
     */
    public int $number;

    /**
     * @param string $heading
     * @param int    $number
     */
    public function __construct(string $heading = 'Intro', int $number = 1)
    {
        $this->heading = $heading;
        $this->number  = $number;
    }
}
