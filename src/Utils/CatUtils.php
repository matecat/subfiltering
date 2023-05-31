<?php

namespace Matecat\SubFiltering\Utils;

class CatUtils {
    const lfPlaceholder   = '##$_0A$##';
    const crPlaceholder   = '##$_0D$##';
    const nbspPlaceholder = '##$_A0$##';

    /**
     * Get the char code from a multi byte char
     *
     * 2/3 times faster than the old implementation
     *
     * @param $mb_char string Unicode Multibyte Char String
     *
     * @return int
     *
     */
    public static function fastUnicode2ord( $mb_char ) {
        switch ( strlen( $mb_char ) ) {
            case 1:
                return ord( $mb_char );
                break;
            case 2:
                return ( ord( $mb_char[ 0 ] ) - 0xC0 ) * 0x40 +
                        ord( $mb_char[ 1 ] ) - 0x80;
                break;
            case 3:
                return ( ord( $mb_char[ 0 ] ) - 0xE0 ) * 0x1000 +
                        ( ord( $mb_char[ 1 ] ) - 0x80 ) * 0x40 +
                        ord( $mb_char[ 2 ] ) - 0x80;
                break;
            case 4:
                return ( ord( $mb_char[ 0 ] ) - 0xF0 ) * 0x40000 +
                        ( ord( $mb_char[ 1 ] ) - 0x80 ) * 0x1000 +
                        ( ord( $mb_char[ 2 ] ) - 0x80 ) * 0x40 +
                        ord( $mb_char[ 3 ] ) - 0x80;
                break;
        }

        return 20; //as default return a space ( should never happen )
    }

    /**
     * @param $str
     *
     * @return string
     */
    public static function htmlentitiesFromUnicode( $str ) {
        return "&#" . self::fastUnicode2ord( $str[ 1 ] ) . ";";
    }

    /**
     * multibyte string manipulation functions
     * source : http://stackoverflow.com/questions/9361303/can-i-get-the-unicode-value-of-a-character-or-vise-versa-with-php
     * original source : PHPExcel libary (http://phpexcel.codeplex.com/)
     * get the char from unicode code
     *
     * @param $o
     *
     * @return string
     */
    public static function unicode2chr( $o ) {
        if ( function_exists( 'mb_convert_encoding' ) ) {
            return mb_convert_encoding( '&#' . intval( $o ) . ';', 'UTF-8', 'HTML-ENTITIES' );
        }

        return chr( intval( $o ) );
    }
}