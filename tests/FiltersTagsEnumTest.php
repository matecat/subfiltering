<?php

use Matecat\SubFiltering\Enum\InjectableFiltersTags;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\MarkupToPh;
use Matecat\SubFiltering\Filters\PercentDoubleCurlyBrackets;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\Filters\DollarCurlyBrackets;
use Matecat\SubFiltering\Filters\DoublePercentages;
use Matecat\SubFiltering\Filters\ObjectiveCNSString;
use Matecat\SubFiltering\Filters\RubyOnRailsI18n;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\Snails;
use Matecat\SubFiltering\Filters\SquareSprintf;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 18/09/25
 * Time: 10:53
 *
 */
class FiltersTagsEnumTest extends TestCase
{

    /**
     * Ensures that forName() returns the correct class name for a valid tag.
     * @test
     */
    public function test_forNameReturnsCorrectClassForValidTag()
    {
        $this->assertEquals(MarkupToPh::class, InjectableFiltersTags::classForTagName('markup'));
        $this->assertEquals(
            PercentDoubleCurlyBrackets::class,
            InjectableFiltersTags::classForTagName('percent_double_curly')
        );
        $this->assertEquals(TwigToPh::class, InjectableFiltersTags::classForTagName('twig'));
    }

    /**
     * Ensures that forName() returns null for an invalid tag.
     * @test
     */
    public function test_forNameReturnsUnknownForInvalidTag()
    {
        $this->assertEquals(null, InjectableFiltersTags::classForTagName('non_existent_tag'));
    }

    /**
     * Ensures that forName() handles an empty string as input.
     * @test
     */
    public function test_forNameHandlesEmptyString()
    {
        $this->assertEquals(null, InjectableFiltersTags::classForTagName(''));
    }

    /**
     * Ensures that forName() handles a null input gracefully.
     * @test
     */
    public function test_forNameHandlesNullInput()
    {
        $this->assertEquals(null, InjectableFiltersTags::classForTagName(null));
    }

    /**
     * Ensures that forArrayNames() maps tag names to their handler class names,
     * preserving order and removing unrecognized tags.
     * @test
     */
    public function test_forArrayNamesMapsTagsToClassesAndUnknown()
    {
        $input = [
            'markup',
            'non_existent_tag',   // should become null and filtered out
            'double_square',
            'sprintf',
        ];

        $expected = [
            MarkupToPh::class,
            DoubleSquareBrackets::class,
            SprintfToPH::class,
        ];

        $this->assertSame($expected, InjectableFiltersTags::classesForArrayTagNames($input));
    }

    /**
     * Ensures that forArrayNames() returns an empty array when called without arguments.
     * @test
     */
    public function test_forArrayNamesWithNoArgumentsReturnsEmptyArray()
    {
        $this->assertSame([], InjectableFiltersTags::classesForArrayTagNames());
    }

    /**
     * Ensures that forArrayNames() returns an empty array when called without arguments.
     * @test
     */
    public function test_forArrayNamesWithNullArgumentReturnsNull()
    {
        $this->assertSame(null, InjectableFiltersTags::classesForArrayTagNames(null));
    }

    /**
     * Ensures that tagForClassName() returns the correct tag for a valid class name.
     * @test
     */
    public function test_tagForClassNameReturnsCorrectTagForValidClass()
    {
        $this->assertSame('markup', InjectableFiltersTags::tagForClassName(MarkupToPh::class));
        $this->assertSame(
            'percent_double_curly',
            InjectableFiltersTags::    tagForClassName(PercentDoubleCurlyBrackets::class)
        );
    }

    /**
     * Ensures that tagForClassName() returns null for an unknown class name.
     * @test
     */
    public function test_tagForClassNameReturnsNullForUnknownClass()
    {
        // Not present in the reverse map
        $this->assertNull(InjectableFiltersTags::tagForClassName(stdClass::class));
    }

    /**
     * Ensures that tagForClassName() covers all mapped handler classes.
     * @test
     */
    public function test_tagForClassNameCoversAllMappedClasses()
    {
        $expectedMap = [
            MarkupToPh::class => 'markup',
            PercentDoubleCurlyBrackets::class => 'percent_double_curly',
            TwigToPh::class => 'twig',
            RubyOnRailsI18n::class => 'ruby_on_rails',
            Snails::class => 'double_snail',
            DoubleSquareBrackets::class => 'double_square',
            DollarCurlyBrackets::class => 'dollar_curly',
            SingleCurlyBracketsToPh::class => 'single_curly',
            ObjectiveCNSString::class => 'objective_c_ns',
            DoublePercentages::class => 'double_percent',
            SquareSprintf::class => 'square_sprintf',
            SprintfToPH::class => 'sprintf',
        ];

        foreach ($expectedMap as $className => $tagName) {
            $this->assertSame($tagName, InjectableFiltersTags::tagForClassName($className));
        }
    }

    /**
     * Ensures that tagNamesForArrayClasses() maps class names to tag names,
     * preserves order, removes unknowns and handles duplicates properly.
     * @test
     */
    public function test_tagNamesForArrayClassesMapsClassesToTags()
    {
        $input = [
            PercentDoubleCurlyBrackets::class,         // percent_double_curly
            SprintfToPH::class,                        // sprintf
            MarkupToPh::class,                            // xml
            PercentDoubleCurlyBrackets::class,         // duplicate -> kept, mapping is deterministic
        ];

        $expected = [
            'percent_double_curly',
            'sprintf',
            'markup',
            'percent_double_curly',
        ];

        $this->assertSame($expected, InjectableFiltersTags::tagNamesForArrayClasses($input));
    }

    /**
     * Ensures that tagNamesForArrayClasses() returns an empty array by default (when called with no args).
     * @test
     */
    public function test_tagNamesForArrayClassesWithDefaultArgumentReturnsEmptyArray()
    {
        $this->assertSame([], InjectableFiltersTags::tagNamesForArrayClasses());
    }

    /**
     * Ensures that tagNamesForArrayClasses() returns null when input is explicitly null.
     * @test
     */
    public function test_tagNamesForArrayClassesWithNullReturnsNull()
    {
        $this->assertNull(InjectableFiltersTags::tagNamesForArrayClasses(null));
    }

    /**
     * Ensures that getTags() returns exactly the keys of the internal tag map in the defined order.
     * @test
     */
    public function test_getTagsReturnsAllTagKeysInOrder()
    {
        $expected = [
            InjectableFiltersTags::markup->value,
            InjectableFiltersTags::percent_double_curly->value,
            InjectableFiltersTags::twig->value,
            InjectableFiltersTags::ruby_on_rails->value,
            InjectableFiltersTags::double_snail->value,
            InjectableFiltersTags::double_square->value,
            InjectableFiltersTags::dollar_curly->value,
            InjectableFiltersTags::single_curly->value,
            InjectableFiltersTags::objective_c_ns->value,
            InjectableFiltersTags::double_percent->value,
            InjectableFiltersTags::square_sprintf->value,
            InjectableFiltersTags::sprintf->value,
        ];

        $this->assertSame($expected, InjectableFiltersTags::getTags());
    }

}