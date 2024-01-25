<?php

/**
 * This file is part of the package magicsunday/xmlmapper.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace MagicSunday\Test;

use Closure;
use MagicSunday\XmlEncoder;
use MagicSunday\XmlMapper\Converter\CamelCasePropertyNameConverter;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

/**
 * Class XmlMapperTest
 *
 * @author  Rico Sonntag <mail@ricosonntag.de>
 * @license https://opensource.org/licenses/MIT
 * @link    https://github.com/magicsunday/xmlmapper/
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Returns an instance of the XmlEncoder for testing.
     *
     * @param string[]|Closure[] $classMap
     *
     * @return XmlEncoder
     */
    protected function getXmlEncoder(array $classMap = []): XmlEncoder
    {
        $listExtractors = [ new ReflectionExtractor() ];
        $typeExtractors = [ new PhpDocExtractor() ];
        $extractor      = new PropertyInfoExtractor($listExtractors, $typeExtractors);

        return new XmlEncoder(
            $extractor,
            new CamelCasePropertyNameConverter()
        );
    }
}
