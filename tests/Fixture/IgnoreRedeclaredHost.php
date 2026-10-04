<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

/**
 * Redeclares the ignored property of its parent without repeating the marker.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class IgnoreRedeclaredHost extends IgnoreBaseHost
{
    /**
     * The own declaration of the concrete class, which carries no marker.
     *
     * @var string
     */
    protected string $token = 'child-secret';
}
