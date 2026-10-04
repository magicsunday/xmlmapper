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
 * Lists an author first, whose converter runs a nested map() call on a node, and
 * the same node as a plain property second, so a test can tell whether the nested
 * call left the node behind as still being encoded.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class NestedMapStateHost implements XmlSerializable
{
    /**
     * @var Author|null
     */
    public ?Author $author = null;

    /**
     * The node the nested map() call of the author converter starts from.
     *
     * @var CyclicNode|null
     */
    public ?CyclicNode $shared = null;
}
