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
use Doctrine\Common\Annotations\Annotation;

/**
 * This annotation is used to inform the XmlMapper that when converting to XML,
 * the property should be added directly as the content of the XML element. It can
 * be applied either as a native PHP attribute (#[XmlNodeValue]) or as a Doctrine
 * docblock annotation (@XmlNodeValue).
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 *
 * @Annotation
 *
 * @Target({"PROPERTY"})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class XmlNodeValue extends Annotation
{
}
