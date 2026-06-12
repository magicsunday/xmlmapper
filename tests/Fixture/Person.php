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
 * A scalar-only fixture covering string, int, bool, nullable and snake_case properties.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class Person implements XmlSerializable
{
    /**
     * @var string
     */
    public string $firstName = 'John';

    /**
     * A snake_case property to exercise the name converter.
     *
     * @var string
     */
    public string $home_town = 'Berlin';

    /**
     * @var int
     */
    public int $age = 42;

    /**
     * @var bool
     */
    public bool $active = true;

    /**
     * A null value which must be skipped during encoding.
     *
     * @var string|null
     */
    public ?string $nickname = null;
}
