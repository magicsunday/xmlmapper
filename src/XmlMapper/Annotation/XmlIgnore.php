<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\XmlMapper\Annotation;

use Attribute;

/**
 * This attribute informs the XmlMapper that the property must not appear in the
 * XML at all. Apply it as a native PHP attribute (#[XmlIgnore]).
 *
 * The property is not read, so no custom type converter runs for it. The marker
 * takes precedence over every other marker on the same property. Like every
 * marker it is read from the property declaration of the concrete class, so a
 * subclass that redeclares the property has to repeat it.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class XmlIgnore
{
}
