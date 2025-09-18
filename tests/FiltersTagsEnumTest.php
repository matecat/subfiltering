<?php

use Matecat\SubFiltering\Enum\InjectableFiltersTags;
use Matecat\SubFiltering\Filters\DoubleSquareBrackets;
use Matecat\SubFiltering\Filters\PercentDoubleCurlyBrackets;
use Matecat\SubFiltering\Filters\SprintfToPH;
use Matecat\SubFiltering\Filters\TwigToPh;
use Matecat\SubFiltering\Filters\XmlToPh;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 18/09/25
 * Time: 10:53
 *
 */
class FiltersTagsEnumTest extends TestCase {

    /**
     * Ensures that forName() returns the correct class name for a valid tag.
     * @test
     */
    public function test_forNameReturnsCorrectClassForValidTag() {
        $this->assertEquals( XmlToPh::class, InjectableFiltersTags::classForTagName( 'xml' ) );
        $this->assertEquals( PercentDoubleCurlyBrackets::class, InjectableFiltersTags::classForTagName( 'percent_double_curly' ) );
        $this->assertEquals( TwigToPh::class, InjectableFiltersTags::classForTagName( 'twig' ) );
    }

    /**
     * Ensures that forName() returns null for an invalid tag.
     * @test
     */
    public function test_forNameReturnsUnknownForInvalidTag() {
        $this->assertEquals( null, InjectableFiltersTags::classForTagName( 'non_existent_tag' ) );
    }

    /**
     * Ensures that forName() handles an empty string as input.
     * @test
     */
    public function test_forNameHandlesEmptyString() {
        $this->assertEquals( null, InjectableFiltersTags::classForTagName( '' ) );
    }

    /**
     * Ensures that forName() handles a null input gracefully.
     * @test
     */
    public function test_forNameHandlesNullInput() {
        $this->assertEquals( null, InjectableFiltersTags::classForTagName( null ) );
    }

    /**
     * Ensures that forArrayNames() maps tag names to their handler class names,
     * preserving order and removing unrecognized tags.
     * @test
     */
    public function test_forArrayNamesMapsTagsToClassesAndUnknown() {
        $input = [
                'xml',
                'non_existent_tag',   // should become null and filtered out
                'double_square',
                'sprintf',
        ];

        $expected = [
                XmlToPh::class,
                DoubleSquareBrackets::class,
                SprintfToPH::class,
        ];

        $this->assertSame( $expected, InjectableFiltersTags::classesForArrayTagNames( $input ) );
    }

    /**
     * Ensures that forArrayNames() returns an empty array when called without arguments.
     * @test
     */
    public function test_forArrayNamesWithNoArgumentsReturnsEmptyArray() {
        $this->assertSame( [], InjectableFiltersTags::classesForArrayTagNames() );
    }

    /**
     * Ensures that forArrayNames() returns an empty array when called without arguments.
     * @test
     */
    public function test_forArrayNamesWithNullArgumentReturnsNull() {
        $this->assertSame( null, InjectableFiltersTags::classesForArrayTagNames( null ) );
    }

    /**
     * Ensures that tagForClassName() returns the correct tag for a valid class name.
     * @test
     */
    public function test_tagForClassNameReturnsCorrectTagForValidClass() {
        $this->assertSame( 'xml', InjectableFiltersTags::tagForClassName( XmlToPh::class ) );
        $this->assertSame( 'percent_double_curly', InjectableFiltersTags::    tagForClassName( PercentDoubleCurlyBrackets::class ) );
    }

    /**
     * Ensures that tagForClassName() returns null for an unknown class name.
     * @test
     */
    public function test_tagForClassNameReturnsNullForUnknownClass() {
        // Not present in the reverse map
        $this->assertNull( InjectableFiltersTags::tagForClassName( stdClass::class ) );
    }

    /**
     * Ensures that tagNamesForArrayClasses() maps class names to tag names,
     * preserves order, removes unknowns and handles duplicates properly.
     * @test
     */
    public function test_tagNamesForArrayClassesMapsClassesToTags() {
        $input = [
                PercentDoubleCurlyBrackets::class,         // percent_double_curly
                SprintfToPH::class,                        // sprintf
                XmlToPh::class,                            // xml
                PercentDoubleCurlyBrackets::class,         // duplicate -> kept, mapping is deterministic
        ];

        $expected = [
                'percent_double_curly',
                'sprintf',
                'xml',
                'percent_double_curly',
        ];

        $this->assertSame( $expected, InjectableFiltersTags::tagNamesForArrayClasses( $input ) );
    }

    /**
     * Ensures that tagNamesForArrayClasses() returns an empty array by default (when called with no args).
     * @test
     */
    public function test_tagNamesForArrayClassesWithDefaultArgumentReturnsEmptyArray() {
        $this->assertSame( [], InjectableFiltersTags::tagNamesForArrayClasses() );
    }

    /**
     * Ensures that tagNamesForArrayClasses() returns null when input is explicitly null.
     * @test
     */
    public function test_tagNamesForArrayClassesWithNullReturnsNull() {
        $this->assertNull( InjectableFiltersTags::tagNamesForArrayClasses( null ) );
    }

    /**
     * Ensures that getTags() returns exactly the keys of the internal tag map in the defined order.
     * @test
     */
    public function test_getTagsReturnsAllTagKeysInOrder() {
        $expected = [
                InjectableFiltersTags::xml,
                InjectableFiltersTags::percent_double_curly,
                InjectableFiltersTags::twig,
                InjectableFiltersTags::ruby_on_rails,
                InjectableFiltersTags::double_snail,
                InjectableFiltersTags::double_square,
                InjectableFiltersTags::dollar_curly,
                InjectableFiltersTags::single_curly,
                InjectableFiltersTags::objective_c_ns,
                InjectableFiltersTags::double_percent,
                InjectableFiltersTags::square_sprintf,
                InjectableFiltersTags::sprintf,
        ];

        $this->assertSame($expected, InjectableFiltersTags::getTags());
    }

}