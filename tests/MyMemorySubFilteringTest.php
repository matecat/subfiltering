<?php

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Filter;
use Matecat\SubFiltering\MyMemoryFilter;
use Matecat\SubFiltering\Tests\Mocks\Features\AirbnbFeature;
use Matecat\SubFiltering\Tests\Mocks\FeatureSet;
use PHPUnit\Framework\TestCase;

class MyMemorySubFilteringTest extends TestCase
{
    /**
     * @return Filter
     * @throws \Exception
     */
    private function getFilterInstance()
    {
        $featureSet = new FeatureSet([new AirbnbFeature()]);

        return MyMemoryFilter::getInstance($featureSet, 'en-US','it-IT', []);
    }

    public function testVariablesWithHTML()
    {
        $filter = $this->getFilterInstance();

        $db_segment      = 'Airbnb account.%{\n}%{&lt;br&gt;}%{\n}1) From ';
        $segment_from_UI = 'Airbnb account.<ph id="mtc_1" equiv-text="base64:JXtcbn0="/>%{<ph id="mtc_2" equiv-text="base64:Jmx0O2JyJmd0Ow=="/>}<ph id="mtc_3" equiv-text="base64:JXtcbn0="/>1) From ';

        $this->assertEquals( $db_segment, $filter->fromLayer1ToLayer0( $segment_from_UI ) );
        $this->assertEquals( $segment_from_UI, $filter->fromLayer0ToLayer1( $db_segment ) );
    }
}