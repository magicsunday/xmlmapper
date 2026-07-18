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
 * Holds a nested object that does not implement XmlSerializable, which is the
 * most likely mistake when adding a new node type.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class UnmarkedNested implements XmlSerializable
{
    /**
     * @var object
     */
    public object $inner;

    /**
     * Seeds the nested object with an anonymous class that does not implement
     * the marker interface.
     */
    public function __construct()
    {
        $this->inner = new class {
            public string $value = 'lost';
        };
    }
}
