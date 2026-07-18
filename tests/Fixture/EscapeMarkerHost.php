<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use MagicSunday\XmlMapper\Annotation\XmlAttribute;
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;
use MagicSunday\XmlSerializable;

/**
 * Carries XML-significant characters through the text-node write path.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class EscapeMarkerHost implements XmlSerializable
{
    /**
     * @var string
     */
    #[XmlAttribute]
    public string $currency = 'EUR & GBP';

    /**
     * @var string
     */
    #[XmlNodeValue]
    public string $amount = 'raw & <b>text</b>';
}
