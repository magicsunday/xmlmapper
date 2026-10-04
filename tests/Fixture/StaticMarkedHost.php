<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use MagicSunday\XmlMapper\Annotation\XmlAttribute;
use MagicSunday\XmlSerializable;

/**
 * Declares static properties that something else could pick up: one carries an
 * XML marker and one has a declared type a type converter can be registered for.
 * Neither belongs to the object, so neither may be read.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class StaticMarkedHost implements XmlSerializable
{
    /**
     * A marked static property, which would be written as an attribute.
     *
     * @var string
     */
    #[XmlAttribute]
    public static string $attribute = 'class-level';

    /**
     * A static property of a type a converter can be registered for.
     *
     * @var int
     */
    public static int $count = 3;

    /**
     * Belongs to this object.
     *
     * @var string
     */
    public string $own = 'instance';
}
