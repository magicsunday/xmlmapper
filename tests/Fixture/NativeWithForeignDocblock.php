<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Fixture;

use MagicSunday\XmlMapper\Annotation\XmlCDataSection;
use MagicSunday\XmlSerializable;

/**
 * A fixture whose property uses a native marker and additionally carries an
 * unrelated docblock annotation from another library. The encoder inspects only
 * native attributes, so the stray docblock annotation must not affect the output.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class NativeWithForeignDocblock implements XmlSerializable
{
    /**
     * @Foo\Bar
     */
    #[XmlCDataSection]
    public string $body = '<b>hi</b>';
}
