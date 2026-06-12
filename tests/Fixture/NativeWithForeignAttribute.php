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
 * A fixture whose properties carry a foreign native attribute: one alongside a
 * marker (the marker must still be resolved), one on its own (no marker must be
 * inferred). Exercises the single-pass attribute scan's skip branch.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class NativeWithForeignAttribute implements XmlSerializable
{
    /**
     * A foreign attribute precedes the marker; the marker must still resolve.
     *
     * @var string
     */
    #[ForeignMarker]
    #[XmlAttribute]
    public string $currency = 'EUR';

    /**
     * Only a foreign attribute; no marker must be inferred.
     *
     * @var string
     */
    #[ForeignMarker]
    public string $label = 'x';
}
