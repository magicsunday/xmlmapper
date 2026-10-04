<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use Iterator;
use MagicSunday\XmlSerializable;

/**
 * Holds a collection as an iterator instead of an array.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class IteratorCollectionHost implements XmlSerializable
{
    /**
     * The authors, as an iterator.
     *
     * @var Iterator<int, Author>
     */
    public Iterator $authors;
}
