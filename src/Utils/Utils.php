<?php

namespace Matecat\SubFiltering\Utils;

class Utils
{

    /**
     * Get the char code from a multibyte char
     *
     * 2/3 times faster than the old implementation
     *
     * @param $mb_char string Unicode Multibyte Char String
     *
     * @return int
     *
     */
    public static function fastUnicode2ord(string $mb_char): int
    {
        return match (strlen($mb_char)) {
            1 => ord($mb_char),
            2 => (ord($mb_char[0]) - 0xC0) * 0x40 +
                ord($mb_char[1]) - 0x80,
            3 => (ord($mb_char[0]) - 0xE0) * 0x1000 +
                (ord($mb_char[1]) - 0x80) * 0x40 +
                ord($mb_char[2]) - 0x80,
            4 => (ord($mb_char[0]) - 0xF0) * 0x40000 +
                (ord($mb_char[1]) - 0x80) * 0x1000 +
                (ord($mb_char[2]) - 0x80) * 0x40 +
                ord($mb_char[3]) - 0x80,
            default => 20,
        };
        //as default, return a space (should never happen)
    }

    /**
     * @param array<int,string> $str
     *
     * @return string
     */
    public static function htmlentitiesFromUnicode(array $str): string
    {
        return "&#" . self::fastUnicode2ord($str[1]) . ";";
    }

    /**
     * multibyte string manipulation functions
     * source : http://stackoverflow.com/questions/9361303/can-i-get-the-unicode-value-of-a-character-or-vise-versa-with-php
     * original source: PHPExcel libary (http://phpexcel.codeplex.com/)
     * get the char from Unicode code
     *
     * @param int $o
     *
     * @return string
     */
    public static function unicode2chr(int $o): string
    {
        return (string)mb_convert_encoding('&#' . $o . ';', 'UTF-8', 'HTML-ENTITIES');
    }

}