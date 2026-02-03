<?php

namespace Matecat\SubFiltering\Enum;

use ReflectionClass;

class CTypeEnum
{
    // Layer 1
    public const string ORIGINAL_X = 'x-original_x';
    public const string ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT = 'x-original_ph';
    public const string ORIGINAL_PH_CONTENT = 'x-original_ph_content';
    public const string HTML = 'x-html';
    public const string XML = 'x-xml';
    public const string TWIG = 'x-twig';
    public const string RUBY_ON_RAILS = 'x-ruby-on-rails';
    public const string SNAILS = 'x-snails';
    public const string CURLY_BRACKETS = 'x-curly-brackets';
    public const string OBJECTIVE_C_NSSTRING = 'x-objective-c-ns-string';
    public const string PERCENTAGES = 'x-percentages';
    public const string SPRINTF = 'x-sprintf';
    public const string PERCENT_VARIABLE = 'x-percent-variable';
    public const string SMART_COUNT = 'x-smart-count';
    public const string DOUBLE_SQUARE_BRACKETS = 'x-double-square-brackets';
    public const string DOLLAR_CURLY_BRACKETS = 'x-dollar-curly-brackets';
    public const string SQUARE_SPRINTF = 'x-square-sprintf';

    // Datstring a Ref Layer 2
    public const string ORIGINAL_PC_OPEN_NO_DATA_REF = 'x-original_pc_open';
    public const string ORIGINAL_PC_CLOSE_NO_DATA_REF = 'x-original_pc_close';
    public const string ORIGINAL_PH_OR_NOT_DATA_REF = 'x-original_ph_no_data_ref';
    public const string PH_DATA_REF = 'x-ph_data_ref';
    public const string PC_OPEN_DATA_REF = 'x-pc_open_data_ref';
    public const string PC_CLOSE_DATA_REF = 'x-pc_close_data_ref';
    public const string PC_SELF_CLOSE_DATA_REF = 'x-pc_sc_data_ref';
    public const string SC_DATA_REF = 'x-sc_data_ref';
    public const string EC_DATA_REF = 'x-ec_data_ref';

    /**
     * @var array<string, string>
     * @phpstan-var array<string, string>
     */
    protected static array $allConstantValues = [];

    /**
     * @var array<string, string>
     * @phpstan-var array<string, string>
     */
    protected static array $layer2ConstantValues = [];

    /**
     * @return array<string, array<string, string>>
     */
    protected static function getAllConstantValuesMap(): array
    {
        if (empty(static::$allConstantValues)) {
            $reflectedProperty = (new ReflectionClass(static::class))->getConstants();
            static::$allConstantValues = array_flip($reflectedProperty);
            static::$layer2ConstantValues = array_flip(
                array_filter($reflectedProperty, function ($key) {
                    return str_contains($key, 'DATA_REF');
                }, ARRAY_FILTER_USE_KEY)
            );
        }

        return ['all' => static::$allConstantValues, 'layer2' => static::$layer2ConstantValues];
    }

    /**
     * @param $ctype string
     *
     * @return bool
     */
    public static function isMatecatCType(string $ctype): bool
    {
        return array_key_exists($ctype, static::getAllConstantValuesMap()['all']);
    }

    /**
     * @param $ctype string
     *
     * @return bool
     */
    public static function isLayer2Constant(string $ctype): bool
    {
        return array_key_exists($ctype, static::getAllConstantValuesMap()['layer2']);
    }

}
