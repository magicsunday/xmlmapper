<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\XmlMapper\Annotation;

use Doctrine\Common\Annotations\Annotation;

/**
 * This annotation is used to inform the XmlMapper that the property should be added
 * as an XML attribute when converting to XML.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 *
 * @Annotation
 *
 * @Target({"PROPERTY"})
 */
final class XmlAttribute extends Annotation
{
}
