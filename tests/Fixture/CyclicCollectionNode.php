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
 * An object that holds a collection of its own kind, so a test can close a cycle
 * through a collection entry.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class CyclicCollectionNode implements XmlSerializable
{
    /**
     * The child nodes, which may contain this node itself.
     *
     * @var CyclicCollectionNode[]
     */
    public array $children = [];

    /**
     * A reference that comes after the collection, so the path at that point
     * shows whether a step of the collection was left behind.
     *
     * @var CyclicCollectionNode|null
     */
    public ?CyclicCollectionNode $parent = null;
}
