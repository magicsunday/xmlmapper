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
 * A fixture covering attributes, nested objects, nullable objects and collections.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class Book implements XmlSerializable
{
    /**
     * @XmlAttribute
     *
     * @var string
     */
    public string $isbn = '978-3-16-148410-0';

    /**
     * @var string
     */
    public string $title = 'The Title';

    /**
     * A nested object.
     *
     * @var Author
     */
    public Author $author;

    /**
     * A nullable object which is populated, exercising the nullable-wrapping unwrap path.
     *
     * @var Author|null
     */
    public ?Author $coAuthor = null;

    /**
     * A collection of scalar values.
     *
     * @var string[]
     */
    public array $tags = ['php', 'xml'];

    /**
     * A collection of objects.
     *
     * @var Chapter[]
     */
    public array $chapters = [];

    /**
     * A nullable collection, exercising the unwrap of a nullable wrapper around a
     * collection type.
     *
     * @var string[]|null
     */
    public ?array $labels = null;

    /**
     * An array without a known value type, exercising the default collection
     * value-type fallback (defaults to string).
     *
     * @var array
     */
    public array $misc = ['a', 'b'];
}
