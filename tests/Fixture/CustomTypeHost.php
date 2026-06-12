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
 * A fixture pairing a collection with an object property so custom type closures
 * can be registered under both the "array" and "object" builtin type names.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class CustomTypeHost implements XmlSerializable
{
    /**
     * @var string[]
     */
    public array $items = ['a', 'b'];

    /**
     * @var Author
     */
    public Author $author;
}
