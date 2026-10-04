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
 * Lists an author first and a reference to itself second. A type converter on the
 * author can run a nested map() call before the self reference is reached.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class CycleAfterNestedMapHost implements XmlSerializable
{
    /**
     * @var Author|null
     */
    public ?Author $author = null;

    /**
     * Points back at the host itself.
     *
     * @var CycleAfterNestedMapHost|null
     */
    public ?CycleAfterNestedMapHost $self = null;
}
