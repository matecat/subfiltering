<?php
/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 17/09/25
 * Time: 18:43
 *
 */

namespace Matecat\SubFiltering\Enum;

use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\ObjectiveCNSString;
use Matecat\SubFiltering\Filters\PercentDoubleCurlyBrackets;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\SquareSprintf;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\Filters\XmlToPh;

class InjectableFiltersTags {

    public const  xml                  = 'xml';
    public const  percent_double_curly = 'percent_double_curly';
    public const  twig                 = 'twig';
    public const  ruby_on_rails        = 'ruby_on_rails';
    public const  double_snail         = 'double_snail';
    public const  double_square        = 'double_square';
    public const  dollar_curly         = 'dollar_curly';
    public const  single_curly         = 'single_curly';
    public const  objective_c_ns       = 'objective_c_ns';
    public const  double_percent       = 'double_percent';
    public const  square_sprintf       = 'square_sprintf';
    public const  sprintf              = 'sprintf';

    protected const tagsMap = [
            self::xml                  => XmlToPh::class,
            self::percent_double_curly => PercentDoubleCurlyBrackets::class,
            self::twig                 => TwigToPh::class,
            self::ruby_on_rails        => RubyOnRailsI18n::class,
            self::double_snail         => SNails::class,
            self::double_square        => DoubleSquareBrackets::class,
            self::dollar_curly         => DollarCurlyBrackets::class,
            self::single_curly         => SingleCurlyBracketsToPh::class,
            self::objective_c_ns       => ObjectiveCNSString::class,
            self::double_percent       => DoublePercentages::class,
            self::square_sprintf       => SquareSprintf::class,
            self::sprintf              => SprintfToPH::class,
    ];

    protected const reverseTagMap = [
            self::tagsMap[ self::xml ]                  => self::xml,
            self::tagsMap[ self::percent_double_curly ] => self::percent_double_curly,
            self::tagsMap[ self::twig ]                 => self::twig,
            self::tagsMap[ self::ruby_on_rails ]        => self::ruby_on_rails,
            self::tagsMap[ self::double_snail ]         => self::double_snail,
            self::tagsMap[ self::double_square ]        => self::double_square,
            self::tagsMap[ self::dollar_curly ]         => self::dollar_curly,
            self::tagsMap[ self::single_curly ]         => self::single_curly,
            self::tagsMap[ self::objective_c_ns ]       => self::objective_c_ns,
            self::tagsMap[ self::double_percent ]       => self::double_percent,
            self::tagsMap[ self::square_sprintf ]       => self::square_sprintf,
            self::tagsMap[ self::sprintf ]              => self::sprintf,
    ];

    /**
     * @param ?string $name
     *
     * @return string|null
     */
    public static function classForTagName( ?string $name ): ?string {
        return self::tagsMap[ $name ] ?? null;
    }

    /**
     * Maps an array of tag names to their corresponding handler class names.
     * Unrecognized tags are filtered out.
     *
     * @param array|null $tagNames An array of tag names to be mapped.
     *
     * @return array|null An array of handler class names corresponding to the provided tag names,
     *                    or null if the input is null.
     */
    public static function classesForArrayTagNames( ?array $tagNames = [] ): ?array {

        if ( $tagNames === null ) {
            return null;
        }

        // Map tags to handler class names using the enum
        return array_values(
                array_filter(
                        array_map( static function ( string $tag ) {
                            return self::classForTagName( $tag );
                        }, $tagNames )
                )
        );
    }

    /**
     * Retrieves the list of tags by returning the keys from the tag map.
     *
     * @return array An array of tag keys.
     */
    public static function getTags(): array {
        return array_keys( self::tagsMap );
    }


    /**
     * Retrieves the class name associated with a given tag.
     *
     * @param string $tag The tag for which the corresponding class name is to be retrieved.
     *
     * @return string|null The class name associated with the provided tag, or null if no association exists.
     */
    public static function tagForClassName( string $tag ): ?string {
        return self::reverseTagMap[ $tag ] ?? null;
    }

    /**
     * Retrieves the tag names associated with the given array of class names.
     * If the input array is null, the method will return null.
     *
     * @param array|null $classNames An optional array of class names for which tags are to be retrieved.
     *
     * @return array|null An array of tag names or null if the input is null.
     */
    public static function tagNamesForArrayClasses( ?array $classNames = [] ): ?array {

        if ( $classNames === null ) {
            return null;
        }

        return array_values(
                array_filter(
                        array_map( static function ( string $className ) {
                            return self::tagForClassName( $className );
                        }, $classNames )
                )
        );
    }

}