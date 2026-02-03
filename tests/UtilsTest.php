<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Utils\Utils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UtilsTest extends TestCase
{
    /**
     * Provides various arrays to test the array_is_list function.
     *
     * @return array
     */
    public static function arrayProvider(): array
    {
        return [
            [ // 'empty array'
                'array' => [],
                'expected' => true,
            ],
            [ // 'sequential zero-indexed array (a true list)'
                'array' => ['a', 'b', 'c'],
                'expected' => true,
            ],
            [ // 'sequential zero-indexed array with explicit keys'
                'array' => [0 => 'a', 1 => 'b', 2 => 'c'],
                'expected' => true,
            ],
            [ // 'array with non-sequential keys'
                'array' => [0 => 'a', 2 => 'c'],
                'expected' => false,
            ],
            [ // 'array with non-zero-based keys'
                'array' => [1 => 'a', 2 => 'b'],
                'expected' => false,
            ],
            [ // 'associative array with string keys'
                'array' => ['a' => 'apple', 'b' => 'banana'],
                'expected' => false,
            ],
            [ // 'mixed array with numeric and string keys'
                'array' => [0 => 'a', 'foo' => 'bar'],
                'expected' => false,
            ],
        ];
    }

    /**
     * @dataProvider unicodeOrdProvider
     */
    #[DataProvider('unicodeOrdProvider')]
    public function test_fastUnicode2ord(string $char, int $expected)
    {
        $this->assertEquals($expected, Utils::fastUnicode2ord($char));
    }

    public static function unicodeOrdProvider(): array
    {
        return [
            '1-byte character (A)' => ['A', 65],
            '2-byte character (¢)' => ['¢', 162],
            '3-byte character (€)' => ['€', 8364],
            '4-byte character (😀)' => ['😀', 128512],
            'invalid/long string should default' => ['long string', 20],
        ];
    }

    /**
     * @dataProvider htmlEntitiesProvider
     */
    #[DataProvider('htmlEntitiesProvider')]
    public function test_htmlentitiesFromUnicode(array $match, string $expected)
    {
        $this->assertEquals($expected, Utils::htmlentitiesFromUnicode($match));
    }

    public static function htmlEntitiesProvider(): array
    {
        return [
            'simple character from match' => [['', 'A'], '&#65;'],
            'multibyte character from match' => [['', '€'], '&#8364;'],
        ];
    }

    /**
     * @dataProvider unicodeChrProvider
     */
    #[DataProvider('unicodeChrProvider')]
    public function test_unicode2chr(int $ord, string $expected)
    {
        $this->assertEquals($expected, Utils::unicode2chr($ord));
    }

    public static function unicodeChrProvider(): array
    {
        return [
            'ASCII character' => [65, 'A'],
            'multibyte character' => [8364, '€'],
            'another multibyte' => [128512, '😀'],
        ];
    }
}
