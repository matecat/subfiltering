<?php

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

enum InjectableFiltersTags: string
{
    case markup = 'markup';
    case percent_double_curly = 'percent_double_curly';
    case twig = 'twig';
    case ruby_on_rails = 'ruby_on_rails';
    case double_snail = 'double_snail';
    case double_square = 'double_square';
    case dollar_curly = 'dollar_curly';
    case single_curly = 'single_curly';
    case objective_c_ns = 'objective_c_ns';
    case double_percent = 'double_percent';
    case square_sprintf = 'square_sprintf';
    case sprintf = 'sprintf';

    /**
     * @return class-string<AbstractHandler>
     */
    protected function handlerClass(): string
    {
        return match ($this) {
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
        };
    }

    /**
     * @param class-string<AbstractHandler> $className
     *
     * @return InjectableFiltersTags|null
     */
    protected static function handlerTag(string $className): ?self
    {
        return match ($className) {
            MarkupToPh::class => self::markup,
            PercentDoubleCurlyBrackets::class => self::percent_double_curly,
            TwigToPh::class => self::twig,
            RubyOnRailsI18n::class => self::ruby_on_rails,
            Snails::class => self::double_snail,
            DoubleSquareBrackets::class => self::double_square,
            DollarCurlyBrackets::class => self::dollar_curly,
            SingleCurlyBracketsToPh::class => self::single_curly,
            ObjectiveCNSString::class => self::objective_c_ns,
            DoublePercentages::class => self::double_percent,
            SquareSprintf::class => self::square_sprintf,
            SprintfToPH::class => self::sprintf,
            default => null
        };
    }

    /**
     * @param ?string $name
     *
     * @return class-string<AbstractHandler>|null
     */
    public static function classForTagName(?string $name): ?string
    {
        $case = $name === null ? null : self::tryFrom($name);
        return $case?->handlerClass();
    }

    /**
     * @param string[]|null $tagNames
     *
     * @return array<class-string<AbstractHandler>>|null
     */
    public static function classesForArrayTagNames(?array $tagNames = []): ?array
    {
        if ($tagNames === null) {
            return null;
        }

        return array_values(array_filter(array_map(static function (string $tag) {
            return self::classForTagName($tag);
        }, $tagNames)));
    }

    /**
     * @return array<string>
     */
    public static function getTags(): array
    {
        return array_map(fn(self $c) => $c->value, self::cases());
    }

    /**
     * @param class-string<AbstractHandler> $handlerClass
     *
     * @return string|null
     */
    public static function tagForClassName(string $handlerClass): ?string
    {
        return self::handlerTag($handlerClass)?->value;
    }

    /**
     * @param array<class-string<AbstractHandler>>|null $classNames
     *
     * @return array<string>|null
     */
    public static function tagNamesForArrayClasses(?array $classNames = []): ?array
    {
        if ($classNames === null) {
            return null;
        }

        return array_values(array_filter(array_map(static function (string $className) {
            return self::tagForClassName($className);
        }, $classNames)));
    }
}
