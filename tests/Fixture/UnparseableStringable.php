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
 * A value that is not a string but converts to one that XML 1.0 cannot carry.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class UnparseableStringable implements Stringable
{
    /**
     * Returns a string that contains a control character XML 1.0 does not allow.
     */
    public function __toString(): string
    {
        return "a\x01b";
    }
}
