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
 * This attribute informs the XmlMapper that when converting to XML, the property
 * should be treated as a CDATA section and automatically surrounded by the
 * <![CDATA[ ]]> tag. Apply it as a native PHP attribute (#[XmlCDataSection]).
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class XmlCDataSection
{
}
