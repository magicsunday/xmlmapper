<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use MagicSunday\XmlMapper\Annotation\XmlIgnore;
use MagicSunday\XmlSerializable;

/**
 * Declares an ignored property that a subclass is free to redeclare, so the
 * boundary of the ignore marker across an inheritance chain stays pinned.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class IgnoreBaseHost implements XmlSerializable
{
    /**
     * A protected property that is excluded and reachable through a getter,
     * which is what makes the extractor report it for the subclasses.
     *
     * @var string
     */
    #[XmlIgnore]
    protected string $token = 'base-secret';

    /**
     * Exposes the property, which is what makes the extractor report it.
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }
}
