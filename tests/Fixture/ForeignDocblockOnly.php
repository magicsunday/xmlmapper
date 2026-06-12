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
 * A fixture whose property carries an unresolvable foreign docblock annotation and
 * no native marker. The Doctrine reader cannot resolve the annotation, so the
 * encoder is expected to fail loudly rather than silently mis-encode the property.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class ForeignDocblockOnly implements XmlSerializable
{
    /**
     * @Foo\Bar
     */
    public string $body = 'text';
}
