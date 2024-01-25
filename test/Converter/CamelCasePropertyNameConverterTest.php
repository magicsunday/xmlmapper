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
use PHPUnit\Framework\TestCase;

/**
 * Class CamelCasePropertyNameConverterTest
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class CamelCasePropertyNameConverterTest extends TestCase
{
    /**
     * Tests mapping properties to camel case.
     *
     * @test
     */
    public function checkCamelCasePropertyNameConverter(): void
    {
        $converter = new CamelCasePropertyNameConverter();

        self::assertSame('camelCaseProperty', $converter->convert('camelCaseProperty'));
        self::assertSame('camelCaseProperty', $converter->convert('camel_case_property'));
        self::assertSame('camelCaseProperty', $converter->convert('camel-case-property'));
        self::assertSame('camelCaseProperty', $converter->convert('camel case property'));
        self::assertSame('camelCaseProperty', $converter->convert('Camel Case Property'));
    }
}
