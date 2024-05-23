<?php

namespace Matecat\SubFiltering\Enum;

use ReflectionClass;

class CTypeEnum {

    // Layer 1
    const ORIGINAL_X                             = 'x-original_x';
    const ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT = 'x-original_ph';
    const ORIGINAL_PC_OPEN                       = 'x-original_pc_open';
    const ORIGINAL_PC_CLOSE                        = 'x-original_pc_close';
    const ORIGINAL_PH_CONTENT                      = 'x-original_ph_content';
    const HTML                                     = 'x-html';
    const TWIG                                     = 'x-twig';
    const RUBY_ON_RAILS                            = 'x-ruby-on-rails';
    const SNAILS                                   = 'x-snails';
    const CURLY_BRACKETS                           = 'x-curly-brackets';
    const PERCENT_SNAILS                           = 'x-percent-snails';
    const PERCENT_NUMBER_SNAILS                    = 'x-percent-number-snails';
    const PERCENTAGES                              = 'x-percentages';
    const SPRINTF                                  = 'x-sprintf';
    const PERCENT_VARIABLE                         = 'x-percent-variable';
    const SMART_COUNT                              = 'x-smart-count';
    const DOUBLE_SQUARE_BRACKETS                   = 'x-double-square-brackets';
    const DOLLAR_CURLY_BRACKETS                    = 'x-dollar-curly-brackets';
    const SQUARE_SPRINTF                           = 'x-square-sprintf';

    // Data Ref Layer 2
    const ORIGINAL_PH_OR_NOT_DATA_REF = 'x-original_ph_no_data_ref';
    const PH_DATA_REF                 = 'x-ph_data_ref';
    const PC_OPEN_DATA_REF            = 'x-pc_open_data_ref';
    const PC_CLOSE_DATA_REF           = 'x-pc_close_data_ref';
    const PC_SELF_CLOSE_DATA_REF      = 'x-pc_sc_data_ref';
    const SC_DATA_REF                 = 'x-sc_data_ref';
    const EC_DATA_REF                 = 'x-ec_data_ref';

    protected static $constantsValues = [];

    protected static function getConstantsMap() {
        if ( empty( static::$constantsValues ) ) {
            $reflectedProperty       = ( new ReflectionClass( static::class ) )->getConstants();
            static::$constantsValues = array_flip( $reflectedProperty );
        }

        return static::$constantsValues;
    }

    public static function isMatecatCType( $ctype ) {
        return array_key_exists( $ctype, static::getConstantsMap() );
    }

}