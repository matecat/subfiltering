<?php

namespace Matecat\SubFiltering\Enum;

use Matecat\SubFiltering\Utils\Utils;
use ReflectionClass;

class CTypeEnum {

    // Layer 1
    const ORIGINAL_X                             = 'x-original_x';
    const ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT = 'x-original_ph';
    const ORIGINAL_PH_CONTENT                    = 'x-original_ph_content';
    const HTML                                   = 'x-html';
    const TWIG                                   = 'x-twig';
    const RUBY_ON_RAILS                          = 'x-ruby-on-rails';
    const SNAILS                                 = 'x-snails';
    const CURLY_BRACKETS                         = 'x-curly-brackets';
    const PERCENT_SNAILS                         = 'x-percent-snails';
    const PERCENT_NUMBER_SNAILS                  = 'x-percent-number-snails';
    const PERCENTAGES                            = 'x-percentages';
    const SPRINTF                                = 'x-sprintf';
    const PERCENT_VARIABLE                       = 'x-percent-variable';
    const SMART_COUNT                            = 'x-smart-count';
    const DOUBLE_SQUARE_BRACKETS                 = 'x-double-square-brackets';
    const DOLLAR_CURLY_BRACKETS                  = 'x-dollar-curly-brackets';
    const ICU                                    = 'x-icu';
    const SQUARE_SPRINTF                         = 'x-square-sprintf';

    // Data Ref Layer 2
    const ORIGINAL_PC_OPEN_NO_DATA_REF  = 'x-original_pc_open';
    const ORIGINAL_PC_CLOSE_NO_DATA_REF = 'x-original_pc_close';
    const ORIGINAL_PH_OR_NOT_DATA_REF   = 'x-original_ph_no_data_ref';
    const PH_DATA_REF                   = 'x-ph_data_ref';
    const PC_OPEN_DATA_REF              = 'x-pc_open_data_ref';
    const PC_CLOSE_DATA_REF             = 'x-pc_close_data_ref';
    const PC_SELF_CLOSE_DATA_REF        = 'x-pc_sc_data_ref';
    const SC_DATA_REF                   = 'x-sc_data_ref';
    const EC_DATA_REF                   = 'x-ec_data_ref';

    protected static array $allConstantValues    = [];
    protected static array $layer2ConstantValues = [];

    /**
     * @return array
     */
    protected static function getAllConstantValuesMap(): array {
        if ( empty( static::$allConstantValues ) ) {
            $reflectedProperty            = ( new ReflectionClass( static::class ) )->getConstants();
            static::$allConstantValues    = array_flip( $reflectedProperty );
            static::$layer2ConstantValues = array_flip(
                    array_filter( $reflectedProperty, function ( $key ) {
                        return Utils::contains( 'DATA_REF', $key );
                    }, ARRAY_FILTER_USE_KEY )
            );
        }

        return [ 'all' => static::$allConstantValues, 'layer2' => static::$layer2ConstantValues ];
    }

    /**
     * @param $ctype string
     *
     * @return bool
     */
    public static function isMatecatCType( string $ctype ): bool {
        return array_key_exists( $ctype, static::getAllConstantValuesMap()[ 'all' ] );
    }

    /**
     * @param $ctype string
     *
     * @return bool
     */
    public static function isLayer2Constant( string $ctype ): bool {
        return array_key_exists( $ctype, static::getAllConstantValuesMap()[ 'layer2' ] );
    }

}