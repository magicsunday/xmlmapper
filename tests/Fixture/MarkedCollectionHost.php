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
 * Carries a property that is a collection and a marker at the same time. A
 * marker writes one scalar into one place, so the entries have nowhere to go.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class MarkedCollectionHost implements XmlSerializable
{
    /**
     * A collection that is also marked as an attribute.
     *
     * @var list<string>
     */
    #[XmlAttribute]
    public array $tags = ['a', 'b'];
}
