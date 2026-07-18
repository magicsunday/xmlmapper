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
use MagicSunday\XmlSerializable;

/**
 * Carries characters that are significant in XML through the element and
 * attribute write paths, so their escaping can be compared against each other.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class EscapeHost implements XmlSerializable
{
    /**
     * Written through the element path.
     *
     * @var string
     */
    public string $element = 'Tom & Jerry';

    /**
     * A value that already contains an entity. It has to survive a round trip
     * unchanged instead of losing one level of escaping.
     *
     * @var string
     */
    public string $preencoded = 'x &amp; y';

    /**
     * Written through the attribute path.
     *
     * @var string
     */
    #[XmlAttribute]
    public string $attribute = 'a & b < c';
}
