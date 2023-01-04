<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\MyMemoryFilter;
use Matecat\SubFiltering\Tests\Mocks\FeatureSet;
use PHPUnit\Framework\TestCase;

class MyMemorySubFilteringTest extends TestCase
{
    /**
     * @return \Matecat\SubFiltering\AbstractFilter
     * @throws \Exception
     */
    private function getFilterInstance()
    {
        MyMemoryFilter::destroyInstance(); // for isolation test

        return MyMemoryFilter::getInstance(new FeatureSet(), 'en-US','it-IT', []);
    }

    /**
     * Test for Airbnb
     *
     * @throws \Exception
     */
    public function testVariablesWithHTML()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'Airbnb account.%{\n}%{&lt;br&gt;}%{\n}1) From ';
        $segment_from_UI = 'Airbnb account.<ph id="mtc_1" equiv-text="base64:JXtcbn0="/>%{<ph id="mtc_2" equiv-text="base64:Jmx0O2JyJmd0Ow=="/>}<ph id="mtc_3" equiv-text="base64:JXtcbn0="/>1) From ';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment, 'airbnb' ) );
    }

    /**
     * Test for skyscanner
     * (promoted to global behavior)
     *
     * @throws \Exception
     */
    public function testSingleSnailSyntax()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @this is a variable@ is not valid';
        $segment_from_UI = 'This syntax @this is a variable@ is not valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );

        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @this_is_a_variable@ is valid';
        $segment_from_UI = 'This syntax <ph id="mtc_1" equiv-text="base64:QHRoaXNfaXNfYV92YXJpYWJsZUA="/> is valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    /**
     **************************
     * Skyscanner pipeline
     * (promoted to global behavior)
     **************************
     */

    public function testDoubleSnailSyntax()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @@this is a variable@@ is not valid';
        $segment_from_UI = 'This syntax @@this is a variable@@ is not valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );

        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @@this_is_a_variable@@ is valid';
        $segment_from_UI = 'This syntax <ph id="mtc_1" equiv-text="base64:QEB0aGlzX2lzX2FfdmFyaWFibGVAQA=="/> is valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentDoubleCurlyBracketsSyntax()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'Save up to ​%{{|discount|}} with these hotels';
        $segment_from_UI = 'Save up to ​%<ph id="mtc_1" equiv-text="base64:e3t8ZGlzY291bnR8fX0="/> with these hotels';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentSnailSyntax()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string: %@ is a IOS placeholder %@.';
        $segment_from_UI = 'This string: <ph id="mtc_1" equiv-text="base64:JUA="/> is a IOS placeholder <ph id="mtc_2" equiv-text="base64:JUA="/>.';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testPercentNumberSnailSyntax()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This string: %12$@ is a IOS placeholder %1$@ %14343$@';
        $segment_from_UI = 'This string: <ph id="mtc_1" equiv-text="base64:JTEyJEA="/> is a IOS placeholder <ph id="mtc_2" equiv-text="base64:JTEkQA=="/> <ph id="mtc_3" equiv-text="base64:JTE0MzQzJEA="/>';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }

    public function testDecodeInternalEncodedXliffTags()
    {
        $filter = $this->getFilterInstance();
        $db_segment = '&lt;x id="1"/&gt;&lt;g id="2"&gt;As soon as the tickets are available to the sellers, they will be able to execute the transfer to you. ';
        $segment_received = '<ph id="mtc_1" equiv-text="base64:Jmx0O3ggaWQ9IjEiLyZndDs="/><ph id="mtc_2" equiv-text="base64:Jmx0O2cgaWQ9IjIiJmd0Ow=="/>As soon as the tickets are available to the sellers, they will be able to execute the transfer to you. ';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_received ) );
        $this->assertEquals( $segment_received, $filter->fromLayer0ToLayer1( $db_segment ) );

    }

}