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
 * This attribute informs the XmlMapper that the property should be added as an XML
 * attribute when converting to XML. Apply it as a native PHP attribute (#[XmlAttribute]).
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class XmlAttribute
{
}
