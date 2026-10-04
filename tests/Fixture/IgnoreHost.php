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
use MagicSunday\XmlMapper\Annotation\XmlIgnore;
use MagicSunday\XmlSerializable;

/**
 * Covers the property shapes the ignore marker has to keep out of the output,
 * next to one property that must stay in so the exclusion is not a blanket drop.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class IgnoreHost implements XmlSerializable
{
    /**
     * A plain public property that stays in the output.
     *
     * @var string
     */
    public string $kept = 'kept';

    /**
     * A plain public property that is excluded.
     *
     * @var string
     */
    #[XmlIgnore]
    public string $skipped = 'skipped';

    /**
     * Carries another marker as well: the exclusion has to win over it.
     *
     * @var string
     */
    #[XmlIgnore]
    #[XmlAttribute]
    public string $skippedAttribute = 'skipped-attribute';

    /**
     * A private field the extractor reports through its accessor, which is the
     * shape that would otherwise leak into the output.
     *
     * @var string
     */
    #[XmlIgnore]
    private string $secret = 'token';

    /**
     * A collection of nested objects that is excluded as a whole.
     *
     * @var Chapter[]
     */
    #[XmlIgnore]
    public array $chapters = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->chapters = [new Chapter('Intro', 1)];
    }

    /**
     * Exposes the private field, which is what makes the extractor report it.
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }
}
