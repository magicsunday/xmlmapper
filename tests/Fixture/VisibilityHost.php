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
 * Covers the three property shapes the extractor can report, so the boundary
 * between "listed by the extractor" and "readable as a field" stays pinned.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class VisibilityHost implements XmlSerializable
{
    /**
     * A plain public property.
     *
     * @var string
     */
    public string $visible = 'public';

    /**
     * A private field exposed through a public accessor.
     *
     * @var string
     */
    private string $hidden = 'private-with-getter';

    /**
     * A private field whose accessor formats it, so a field read and an accessor
     * read become distinguishable in the output — the shape the recipe warns
     * about when it says a formatting getter has no effect.
     *
     * @var string
     */
    private string $transformed = 'from-field';

    /**
     * Exposes the private field, which is what makes the extractor report it.
     *
     * @return string
     */
    public function getHidden(): string
    {
        return $this->hidden;
    }

    /**
     * Diverges from its backing field on purpose.
     *
     * @return string
     */
    public function getTransformed(): string
    {
        return strtoupper($this->transformed);
    }

    /**
     * A purely virtual property: an accessor with no backing field.
     *
     * @return string
     */
    public function getComputed(): string
    {
        return 'computed';
    }
}
