<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday;

/**
 * Marks a class as encodable by the XML encoder.
 *
 * Every class passed to XmlEncoder::map(), and every nested object that should
 * be encoded recursively rather than flattened, has to implement it. It declares
 * no methods: the XML representation is shaped by the property marker attributes
 * and by custom type closures, not through this interface.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
interface XmlSerializable
{
}
