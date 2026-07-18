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
 * Holds two differently typed object properties so a custom type registered for
 * one class can be shown not to affect the other.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class MoneyHost implements XmlSerializable
{
    /**
     * @var Money
     */
    public Money $amount;

    /**
     * @var Author
     */
    public Author $author;

    /**
     * Seeds both properties so a class-specific converter can be shown not to
     * affect the other one.
     */
    public function __construct()
    {
        $this->amount = new Money();
        $this->author = new Author();
    }
}
