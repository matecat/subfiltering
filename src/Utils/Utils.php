<?php

namespace Matecat\SubFiltering\Utils;

class Utils {

    /**
     * @param $array
     *
     * @return bool
     */
    public static function array_is_list( $array ) {

        if ( !function_exists( 'array_is_list' ) ) { // since php 8.1

            if ( $array === [] ) {
                return true;
            }

            return array_keys( $array ) === range( 0, count( $array ) - 1 );
        }

        return array_is_list( $array );
    }

    /**
     * @param string $needle
     * @param string $haystack
     *
     * @return bool
     */
    public static function contains( $needle, $haystack ) {
        return strpos( $haystack, $needle ) !== false;
    }

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
    public static function fastUnicode2ord( $mb_char ) {
        switch ( strlen( $mb_char ) ) {
            case 1:
                return ord( $mb_char );
            case 2:
                return ( ord( $mb_char[ 0 ] ) - 0xC0 ) * 0x40 +
                        ord( $mb_char[ 1 ] ) - 0x80;
            case 3:
                return ( ord( $mb_char[ 0 ] ) - 0xE0 ) * 0x1000 +
                        ( ord( $mb_char[ 1 ] ) - 0x80 ) * 0x40 +
                        ord( $mb_char[ 2 ] ) - 0x80;
            case 4:
                return ( ord( $mb_char[ 0 ] ) - 0xF0 ) * 0x40000 +
                        ( ord( $mb_char[ 1 ] ) - 0x80 ) * 0x1000 +
                        ( ord( $mb_char[ 2 ] ) - 0x80 ) * 0x40 +
                        ord( $mb_char[ 3 ] ) - 0x80;
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