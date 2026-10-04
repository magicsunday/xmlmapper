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

/**
 * Redeclares the ignored property of its parent and repeats the marker on it.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class IgnoreRepeatedHost extends IgnoreBaseHost
{
    /**
     * The own declaration of the concrete class, which repeats the marker.
     *
     * @var string
     */
    #[XmlIgnore]
    protected string $token = 'child-secret';
}
