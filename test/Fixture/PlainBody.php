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
 * A fixture whose "body" property carries no marker at all, so its markup must be
 * escaped as a plain element. It shares the property name with {@see Comment},
 * whose "body" is a CDATA section, to discriminate the per-class marker cache.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class PlainBody implements XmlSerializable
{
    /**
     * @var string
     */
    public string $body = '<b>hi</b>';
}
