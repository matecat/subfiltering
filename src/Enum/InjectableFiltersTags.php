<?php

/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 17/09/25
 * Time: 18:43
 *
 */

namespace Matecat\SubFiltering\Enum;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\MarkupToPh;
use Matecat\SubFiltering\Filters\ObjectiveCNSString;
use Matecat\SubFiltering\Filters\PercentDoubleCurlyBrackets;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\SquareSprintf;
use Matecat\SubFiltering\Filters\TwigToPh;

class InjectableFiltersTags
{
    public const  string markup = 'markup';
    public const  string percent_double_curly = 'percent_double_curly';
    public const  string twig = 'twig';
    public const  string ruby_on_rails = 'ruby_on_rails';
    public const  string double_snail = 'double_snail';
    public const  string double_square = 'double_square';
    public const  string dollar_curly = 'dollar_curly';
    public const  string single_curly = 'single_curly';
    public const  string objective_c_ns = 'objective_c_ns';
    public const  string double_percent = 'double_percent';
    public const  string square_sprintf = 'square_sprintf';
    public const  string sprintf = 'sprintf';

    /**
     * @var array<string, class-string<AbstractHandler>>
     */
    protected const array tagsMap = [
        self::markup => MarkupToPh::class,
        self::percent_double_curly => PercentDoubleCurlyBrackets::class,
        self::twig => TwigToPh::class,
        self::ruby_on_rails => RubyOnRailsI18n::class,
        self::double_snail => Snails::class,
        self::double_square => DoubleSquareBrackets::class,
        self::dollar_curly => DollarCurlyBrackets::class,
        self::single_curly => SingleCurlyBracketsToPh::class,
        self::objective_c_ns => ObjectiveCNSString::class,
        self::double_percent => DoublePercentages::class,
        self::square_sprintf => SquareSprintf::class,
        self::sprintf => SprintfToPH::class,
    ];

    /**
     * @var array<class-string<AbstractHandler>, string>
     */
    protected const array reverseTagMap = [
        self::tagsMap[self::markup] => self::markup,
        self::tagsMap[self::percent_double_curly] => self::percent_double_curly,
        self::tagsMap[self::twig] => self::twig,
        self::tagsMap[self::ruby_on_rails] => self::ruby_on_rails,
        self::tagsMap[self::double_snail] => self::double_snail,
        self::tagsMap[self::double_square] => self::double_square,
        self::tagsMap[self::dollar_curly] => self::dollar_curly,
        self::tagsMap[self::single_curly] => self::single_curly,
        self::tagsMap[self::objective_c_ns] => self::objective_c_ns,
        self::tagsMap[self::double_percent] => self::double_percent,
        self::tagsMap[self::square_sprintf] => self::square_sprintf,
        self::tagsMap[self::sprintf] => self::sprintf,
    ];

    /**
     * @param ?string $name
     *
     * @return class-string<AbstractHandler>|null
     */
    public static function classForTagName(?string $name): ?string
    {
        return self::tagsMap[$name] ?? null;
    }

    /**
     * Maps an array of tag names to their corresponding handler class names.
     * Unrecognized tags are filtered out.
     *
     * @param string[]|null $tagNames An array of tag names to be mapped.
     *
     * @return class-string<AbstractHandler>[]|null An array of handler class names corresponding to the provided tag names,
     *                    or null if the input is null.
     */
    public static function classesForArrayTagNames(?array $tagNames = []): ?array
    {
        if ($tagNames === null) {
            return null;
        }

        // Map tags to handler class names using the enum
        return array_values(
            array_filter(
                array_map(static function (string $tag) {
                    return self::classForTagName($tag);
                }, $tagNames)
            )
        );
    }

    /**
     * Retrieves the list of tags by returning the keys from the tag map.
     *
     * @return array<string> An array of tag keys.
     */
    public static function getTags(): array
    {
        return array_keys(self::tagsMap);
    }


    /**
     * Retrieves the class name associated with a given tag.
     *
     * @param string $tag The tag for which the corresponding class name is to be retrieved.
     *
     * @return string|null The class name associated with the provided tag, or null if no association exists.
     */
    public static function tagForClassName(string $tag): ?string
    {
        return self::reverseTagMap[$tag] ?? null;
    }

    /**
     * Retrieves the tag names associated with the given array of class names.
     * If the input array is null, the method will return null.
     *
     * @param array<class-string<AbstractHandler>>|null $classNames An optional array of class names for which tags are to be retrieved.
     *
     * @return array<string>|null An array of tag names or null if the input is null.
     */
    public static function tagNamesForArrayClasses(?array $classNames = []): ?array
    {
        if ($classNames === null) {
            return null;
        }

        return array_values(
            array_filter(
                array_map(static function (string $className) {
                    return self::tagForClassName($className);
                }, $classNames)
            )
        );
    }

}
