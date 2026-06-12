<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use Attribute;

/**
 * A native attribute from an unrelated library that is not one of the encoder's
 * markers. Used to prove that a foreign attribute on a property is ignored.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ForeignMarker
{
}
