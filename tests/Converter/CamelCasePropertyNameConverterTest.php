<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test\Converter;

use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class CamelCasePropertyNameConverterTest.
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
#[CoversClass(CamelCasePropertyNameConverter::class)]
class CamelCasePropertyNameConverterTest extends TestCase
{
    /**
     * Tests mapping properties to camel case.
     */
    #[Test]
    public function checkCamelCasePropertyNameConverter(): void
    {
        $converter = new CamelCasePropertyNameConverter();

        self::assertSame('camelCaseProperty', $converter->convert('camelCaseProperty'));
        self::assertSame('camelCaseProperty', $converter->convert('camel_case_property'));
        self::assertSame('camelCaseProperty', $converter->convert('camel-case-property'));
        self::assertSame('camelCaseProperty', $converter->convert('camel case property'));
        self::assertSame('camelCaseProperty', $converter->convert('Camel Case Property'));
    }

    /**
     * Tests that the converter is a final, read-only value, so it cannot be
     * subclassed into one that holds changing state.
     *
     * Reads state only. It fails on the code before the change, where the
     * class was neither final nor read-only.
     */
    #[Test]
    public function isAFinalReadOnlyClass(): void
    {
        $class = new ReflectionClass(CamelCasePropertyNameConverter::class);

        self::assertTrue($class->isFinal());
        self::assertTrue($class->isReadOnly());
    }
}
