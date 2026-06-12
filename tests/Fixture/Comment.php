<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use MagicSunday\XmlMapper\Annotation\XmlCDataSection;
use MagicSunday\XmlSerializable;

/**
 * A fixture whose property is rendered as a CDATA section.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class Comment implements XmlSerializable
{
    #[XmlCDataSection]
    public string $body = '<b>hi</b>';
}
