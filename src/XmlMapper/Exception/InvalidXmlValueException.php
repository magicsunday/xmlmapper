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

use function bin2hex;
use function sprintf;
use function strtoupper;

/**
 * Thrown when a value cannot be written into an XML 1.0 document, because it
 * contains a character the specification does not allow or is not valid UTF-8.
 * No conforming parser accepts the document such a value would produce, so the
 * value is refused instead of being written.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
final class InvalidXmlValueException extends RuntimeException
{
    /**
     * Creates the exception for a value that contains a character XML 1.0 does not allow.
     *
     * @param string $path      The property path of the value
     * @param string $character The bytes of the offending character
     * @param int    $offset    The byte offset of the character within the value
     */
    public static function forCharacter(string $path, string $character, int $offset): self
    {
        return new self(
            sprintf(
                'Invalid value at "%s": the string contains the byte sequence 0x%s at byte offset %d, which XML 1.0 does not allow.',
                $path,
                strtoupper(bin2hex($character)),
                $offset
            )
        );
    }

    /**
     * Creates the exception for a value that is not valid UTF-8.
     *
     * @param string $path The property path of the value
     */
    public static function forEncoding(string $path): self
    {
        return new self(
            sprintf(
                'Invalid value at "%s": the string is not valid UTF-8.',
                $path
            )
        );
    }
}
