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
 * An object that can point at another one of its own kind, so a test can close a
 * cycle directly on itself or through a second instance.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class CyclicNode implements XmlSerializable
{
    /**
     * The next node, which may be this node itself.
     *
     * @var CyclicNode|null
     */
    public ?CyclicNode $peer = null;

    /**
     * @var string
     */
    public string $label = 'x';
}
