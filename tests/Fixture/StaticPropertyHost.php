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
 * Declares a public static property next to an ordinary one, so a test can tell
 * state of the class from state of the object.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class StaticPropertyHost implements XmlSerializable
{
    /**
     * Belongs to the class and is shared by every instance.
     *
     * @var string
     */
    public static string $shared = 'class-level';

    /**
     * Belongs to this object.
     *
     * @var string
     */
    public string $own = 'instance';
}
