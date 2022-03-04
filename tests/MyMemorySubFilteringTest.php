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
     *
     * @throws \Exception
     */
    public function testSingleSnailSyntax()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @this is a variable@ is not valid';
        $segment_from_UI = 'This syntax @this is a variable@ is not valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment, 'skyscanner' ) );

        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @this_is_a_variable@ is valid';
        $segment_from_UI = 'This syntax <ph id="mtc_1" equiv-text="base64:QHRoaXNfaXNfYV92YXJpYWJsZUA="/> is valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment, 'skyscanner' ) );
    }

    /**
     * Test for skyscanner
     *
     * @throws \Exception
     */
    public function testDoubleSnailSyntax()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @@this is a variable@@ is not valid';
        $segment_from_UI = 'This syntax @@this is a variable@@ is not valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment, 'skyscanner' ) );

        $filter = $this->getFilterInstance();

        $db_segment      = 'This syntax @@this_is_a_variable@@ is valid';
        $segment_from_UI = 'This syntax <ph id="mtc_1" equiv-text="base64:QEB0aGlzX2lzX2FfdmFyaWFibGVAQA=="/> is valid';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment, 'skyscanner' ) );
    }
}