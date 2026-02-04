<?php

namespace Matecat\SubFiltering\Enum;

enum CTypeEnum: string
{
    // Layer 1
    case ORIGINAL_X = 'x-original_x';
    case ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT = 'x-original_ph';
    case ORIGINAL_PH_CONTENT = 'x-original_ph_content';
    case HTML = 'x-html';
    case XML = 'x-xml';
    case TWIG = 'x-twig';
    case RUBY_ON_RAILS = 'x-ruby-on-rails';
    case SNAILS = 'x-snails';
    case CURLY_BRACKETS = 'x-curly-brackets';
    case OBJECTIVE_C_NSSTRING = 'x-objective-c-ns-string';
    case PERCENTAGES = 'x-percentages';
    case SPRINTF = 'x-sprintf';
    case PERCENT_VARIABLE = 'x-percent-variable';
    case SMART_COUNT = 'x-smart-count';
    case DOUBLE_SQUARE_BRACKETS = 'x-double-square-brackets';
    case DOLLAR_CURLY_BRACKETS = 'x-dollar-curly-brackets';
    case SQUARE_SPRINTF = 'x-square-sprintf';

    // Datstring a Ref Layer 2
    case ORIGINAL_PC_OPEN_NO_DATA_REF = 'x-original_pc_open';
    case ORIGINAL_PC_CLOSE_NO_DATA_REF = 'x-original_pc_close';
    case ORIGINAL_PH_OR_NOT_DATA_REF = 'x-original_ph_no_data_ref';
    case PH_DATA_REF = 'x-ph_data_ref';
    case PC_OPEN_DATA_REF = 'x-pc_open_data_ref';
    case PC_CLOSE_DATA_REF = 'x-pc_close_data_ref';
    case PC_SELF_CLOSE_DATA_REF = 'x-pc_sc_data_ref';
    case SC_DATA_REF = 'x-sc_data_ref';
    case EC_DATA_REF = 'x-ec_data_ref';

    public static function isMatecatCType(string $ctype): bool
    {
        return self::tryFrom($ctype) !== null;
    }

    public static function isLayer2Constant(string $ctype): bool
    {
        $case = self::tryFrom($ctype);
        return $case !== null && str_contains($case->name, 'DATA_REF');
    }
}