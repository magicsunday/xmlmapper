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
 * Thrown by a strict encoder when a value cannot be mapped to XML. A lenient
 * encoder writes an empty element or leaves the value out instead, so the data
 * is lost without any signal.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
final class UnmappableValueException extends RuntimeException
{
    /**
     * Creates the exception for a value that is neither a scalar nor Stringable
     * and has no encoding of its own, such as an array that no type extractor
     * recognised as a collection or an object that does not implement the marker.
     *
     * @param string $path The property path of the value
     * @param string $type The type of the value
     */
    public static function forValue(string $path, string $type): self
    {
        return new self(
            sprintf(
                'Cannot map the value at "%s": a value of type %s is neither a scalar nor Stringable, and a strict encoder does not drop it.',
                $path,
                $type
            )
        );
    }

    /**
     * Creates the exception for a collection property whose value cannot be iterated.
     *
     * @param string $path The property path of the collection
     * @param string $type The type of the value
     */
    public static function forCollection(string $path, string $type): self
    {
        return new self(
            sprintf(
                'Cannot map the value at "%s": a collection property holds a value of type %s that cannot be iterated, and a strict encoder does not drop it.',
                $path,
                $type
            )
        );
    }
}
