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
 * A model with a property that has a stored value and a get hook that transforms it.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
final class BackedGetHookHost implements XmlSerializable
{
    /**
     * A property whose stored value differs from the value its get hook returns.
     */
    public string $title = 'stored' {
        get => strtoupper($this->title);
    }
}
