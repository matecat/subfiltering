<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\MyMemoryFilter;
use Matecat\SubFiltering\Tests\Mocks\FeatureSet;
use PHPUnit\Framework\TestCase;

class MyMemorySubFilteringTest extends TestCase {
    /**
     * @return \Matecat\SubFiltering\AbstractFilter
     * @throws \Exception
     */
    private function getFilterInstance() {
        MyMemoryFilter::destroyInstance(); // for isolation test

        return MyMemoryFilter::getInstance( new FeatureSet(), 'en-US', 'it-IT', [] );
    }

    /**
     **************************
     * Roblox pipeline
     **************************
     */

    public function testSingleCurlyBrackets() {
        $filter = $this->getFilterInstance();

        $segment   = "This is a {placeholder}";
        $segmentL1 = $filter->fromLayer0ToLayer1( $segment, 'roblox' );

        $string_from_UI = 'This is a <ph id="mtc_1" ctype="'.CTypeEnum::CURLY_BRACKETS.'" equiv-text="base64:e3BsYWNlaG9sZGVyfQ=="/>';

        $this->assertEquals($segmentL1, $string_from_UI);
        $this->assertEquals( $segment, $filter->fromLayer1ToLayer0( $segmentL1 ) );
    }

    /**
     * Test for Airbnb
     *
     * @throws \Exception
     */
    public function testVariablesWithHTML() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'Airbnb account.%{\n}%{&lt;br&gt;}%{\n}1) From ';
        $segment_from_UI = 'Airbnb account.<ph id="mtc_1" ctype="' . CTypeEnum::RUBY_ON_RAILS . '" equiv-text="base64:JXtcbn0="/>%{<ph id="mtc_2" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O2JyJmd0Ow=="/>}<ph id="mtc_3" ctype="' . CTypeEnum::RUBY_ON_RAILS . '" equiv-text="base64:JXtcbn0="/>1) From ';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment, 'airbnb' ) );
    }

    /**
     * Test for skyscanner
     * (promoted to global behavior)
     *
     * @throws \Exception
     */
    public function testSinglePercentageSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax %this_is_a_variable% is no more valid';
        $segment_from_UI = 'This syntax %this_is_a_variable% is no more valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    /**
     * Test for skyscanner
     * (promoted to global behavior)
     *
     * @throws \Exception
     */
    public function testDoublePercentageSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax %%customer.first_name%% is still valid';
        $segment_from_UI = 'This syntax <ph id="mtc_1" ctype="' . CTypeEnum::PERCENTAGES . '" equiv-text="base64:JSVjdXN0b21lci5maXJzdF9uYW1lJSU="/> is still valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    /**
     * Test for skyscanner
     * (promoted to global behavior)
     *
     * @throws \Exception
     */
    public function testSingleSnailSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @this is a variable@ is not valid';
        $segment_from_UI = 'This syntax @this is a variable@ is not valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );

        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @this_is_a_variable@ is no more valid';
        $segment_from_UI = 'This syntax @this_is_a_variable@ is no more valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    /**
     **************************
     * Skyscanner pipeline
     * (promoted to global behavior)
     **************************
     */

    public function testDoubleSnailSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @@this is a variable@@ is not valid';
        $segment_from_UI = 'This syntax @@this is a variable@@ is not valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );

        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @@this_is_a_variable@@ is valid';
        $segment_from_UI = 'This syntax <ph id="mtc_1" ctype="' . CTypeEnum::SNAILS . '" equiv-text="base64:QEB0aGlzX2lzX2FfdmFyaWFibGVAQA=="/> is valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentDoubleCurlyBracketsSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'Save up to {{|discount|}} with these hotels';
        $segment_from_UI = 'Save up to <ph id="mtc_1" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:e3t8ZGlzY291bnR8fX0="/> with these hotels';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentSnailSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string: %@ is a IOS placeholder %@.';
        $segment_from_UI = 'This string: <ph id="mtc_1" ctype="' . CTypeEnum::PERCENT_SNAILS . '" equiv-text="base64:JUA="/> is a IOS placeholder <ph id="mtc_2" ctype="' . CTypeEnum::PERCENT_SNAILS . '" equiv-text="base64:JUA="/>.';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentNumberSnailSyntax() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string: %12$@ is a IOS placeholder %1$@ %14343$@';
        $segment_from_UI = 'This string: <ph id="mtc_1" ctype="' . CTypeEnum::PERCENT_NUMBER_SNAILS . '" equiv-text="base64:JTEyJEA="/> is a IOS placeholder <ph id="mtc_2" ctype="' . CTypeEnum::PERCENT_NUMBER_SNAILS . '" equiv-text="base64:JTEkQA=="/> <ph id="mtc_3" ctype="' . CTypeEnum::PERCENT_NUMBER_SNAILS . '" equiv-text="base64:JTE0MzQzJEA="/>';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testDecodeInternalEncodedXliffTags() {
        $filter           = $this->getFilterInstance();
        $db_segment       = '&lt;x id="1"/&gt;&lt;g id="2"&gt;As soon as the tickets are available to the sellers, they will be able to execute the transfer to you. ';
        $segment_received = '<ph id="mtc_1" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O3ggaWQ9IjEiLyZndDs="/><ph id="mtc_2" ctype="' . CTypeEnum::HTML . '" equiv-text="base64:Jmx0O2cgaWQ9IjIiJmd0Ow=="/>As soon as the tickets are available to the sellers, they will be able to execute the transfer to you. ';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_received ) );
        $this->assertEquals( $segment_received, $filter->fromLayer0ToLayer1( $db_segment ) );

    }

    /**
     **************************
     * Lastminute pipeline
     * (promoted to global behavior)
     **************************
     */

    public function testWithDoubleSquareBrackets() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string contains [[placeholder]]';
        $segment_from_UI = 'This string contains <ph id="mtc_1" ctype="' . CTypeEnum::DOUBLE_SQUARE_BRACKETS . '" equiv-text="base64:W1twbGFjZWhvbGRlcl1d"/>';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

//    public function testWithDoubleUnderscore()
//    {
//        $filter = $this->getFilterInstance();
//
//        $db_segment      = 'This string contains __placeholder_one__';
//        $segment_from_UI      = 'This string contains <ph id="mtc_1" ctype="'.CTypeEnum::DOUBLE_UNDERSCORE.'" equiv-text="base64:X19wbGFjZWhvbGRlcl9vbmVfXw=="/>';
//
//        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
//        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
//    }

    public function testWithDollarCurlyBrackets() {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string contains ${placeholder_one}';
        $segment_from_UI = 'This string contains <ph id="mtc_1" ctype="' . CTypeEnum::DOLLAR_CURLY_BRACKETS . '" equiv-text="base64:JHtwbGFjZWhvbGRlcl9vbmV9"/>';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testWithSquareSprintf() {
        $filter = $this->getFilterInstance();

        $tags = [
            '[%s]',
            '[%1$s]',
            '[%222$s]',
            '[%s:name]',
            '[%s:placeholder]',
            '[%s:place_holder]',
            '[%i]',
            '[%1$i]',
            '[%222$i]',
            '[%i:name]',
            '[%i:placeholder]',
            '[%i:place_holder]',
            '[%f]',
            '[%.2f]',
            '[%.2332f]',
            '[%1$.2f]',
            '[%23$.24343f]',
            '[%.222f:name]',
            '[%.2f:placeholder]',
            '[%.2f:place_holder]',
            '[%key_id:1234%]',
            '[%test:1234%]',
            '[%.2f:placeholder]',
            '[%1$s:placeholder]',
            '[%1$i:placeholder]',
            '[%f:placeholder]',
            '[%1$.2f:placeholder]',
        ];

        foreach ( $tags as $tag ) {
            $db_segment      = 'Ciao ' . $tag;
            $segment_from_UI = 'Ciao <ph id="mtc_1" ctype="' . CTypeEnum::SQUARE_SPRINTF . '" equiv-text="base64:' . base64_encode( $tag ) . '"/>';

            $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
            $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
        }
    }
}