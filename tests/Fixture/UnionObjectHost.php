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
 * A fixture with collections whose value type is a union, exercising the
 * runtime-value routing of composite collection value types: a union of object
 * types and a union mixing a scalar with an object type.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class UnionObjectHost implements XmlSerializable
{
    /**
     * @var array<int, Author|Chapter>
     */
    public array $members = [];

    /**
     * @var array<int, int|Author>
     */
    public array $mixed = [];
}
