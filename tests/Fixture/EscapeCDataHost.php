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
 * Carries the CDATA terminator through the third write path.
 *
 * Kept apart from the other escape fixtures because a node-value marker and a
 * CDATA marker cannot coexist on one class.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class EscapeCDataHost implements XmlSerializable
{
    /**
     * The one sequence that terminates a CDATA section.
     *
     * @var string
     */
    #[XmlCDataSection]
    public string $note = 'a]]>b';
}
