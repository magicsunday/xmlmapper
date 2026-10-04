<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\XmlMapper\Exception;

use RuntimeException;

use function sprintf;

/**
 * Thrown when an object that is currently being encoded is reached again through
 * one of its own properties, directly or through other objects. Encoding such a
 * graph would never end.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
final class CircularReferenceException extends RuntimeException
{
    /**
     * Creates the exception for the property path that closes the cycle.
     *
     * @param string       $path      The property path at which the object is reached again
     * @param class-string $className The class of the object that is reached again
     */
    public static function atPath(string $path, string $className): self
    {
        return new self(
            sprintf(
                'Circular reference detected at "%s": an object of class %s is already being encoded further up the same path.',
                $path,
                $className
            )
        );
    }
}
