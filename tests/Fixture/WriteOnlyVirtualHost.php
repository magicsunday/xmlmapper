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
 * A model whose properties use property hooks in the shapes the encoder has to tell apart.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
final class WriteOnlyVirtualHost implements XmlSerializable
{
    /**
     * A plain property, encoded like any other.
     */
    public string $filled = 'value';

    /**
     * A virtual property that has a set hook and no get hook, so reading it raises an Error.
     */
    public string $sinkOnly {
        set {
        }
    }

    /**
     * A property whose set hook is backed by a value, which stays readable.
     */
    public string $backed = 'kept' {
        set => $value;
    }

    /**
     * A virtual property that has a get hook next to its set hook, which stays readable.
     */
    public string $readable {
        get => 'read';
        set {
        }
    }
}
