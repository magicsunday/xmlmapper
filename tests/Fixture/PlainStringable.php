<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use Stringable;

/**
 * A value that is not a string but converts to a plain one the encoder can write.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class PlainStringable implements Stringable
{
    /**
     * Returns a string that XML 1.0 can carry.
     */
    public function __toString(): string
    {
        return 'plain';
    }
}
