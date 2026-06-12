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
 * A host holding two child objects that both expose a "body" property but mark it
 * differently: {@see Comment} renders it as a CDATA section, {@see PlainBody} as a
 * plain (escaped) element. Encoding both within one document exercises the marker
 * cache key, which must discriminate by class rather than by property name alone.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class BodyHost implements XmlSerializable
{
    /**
     * @var Comment
     */
    public Comment $rich;

    /**
     * @var PlainBody
     */
    public PlainBody $plain;

    /**
     * Seeds both child objects so the host can be encoded as one document.
     */
    public function __construct()
    {
        $this->rich  = new Comment();
        $this->plain = new PlainBody();
    }
}
