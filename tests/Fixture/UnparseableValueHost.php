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
use MagicSunday\XmlMapper\Annotation\XmlCDataSection;
use MagicSunday\XmlMapper\Annotation\XmlNodeValue;
use MagicSunday\XmlSerializable;
use Stringable;

/**
 * Carries a string through every write path that turns a value into XML text,
 * so a test can put an unusable string into exactly one of them and leave the
 * others valid.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class UnparseableValueHost implements XmlSerializable
{
    /**
     * Written as the text of its own element.
     *
     * @var string
     */
    public string $text = 'ok';

    /**
     * Written as an attribute of the surrounding element.
     *
     * @var string
     */
    #[XmlAttribute]
    public string $attribute = 'ok';

    /**
     * Written as a CDATA section.
     *
     * @var string
     */
    #[XmlCDataSection]
    public string $cdata = 'ok';

    /**
     * Written as the raw text content of the surrounding element.
     *
     * @var string
     */
    #[XmlNodeValue]
    public string $body = 'ok';

    /**
     * Written as the text of its own element, through its string conversion.
     *
     * @var Stringable|string
     */
    public Stringable|string $stringable = 'ok';

    /**
     * Written as one element per entry.
     *
     * @var list<string>
     */
    public array $tags = [];
}
