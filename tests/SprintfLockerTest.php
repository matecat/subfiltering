<?php
/**
 * Created by PhpStorm.
 * @author ostico domenico@translated.net / ostico@gmail.com
 * Date: 15/01/20
 * Time: 15:18
 *
 */

namespace Matecat\SubFiltering\Tests;

use Matecat\SubFiltering\Enum\CTypeEnum;
use Matecat\SubFiltering\MateCatFilter;
use Matecat\SubFiltering\Tests\Mocks\FeatureSet;
use PHPUnit\Framework\TestCase;

class SprintfLockerTest extends TestCase {

    /**
     * @throws \Exception
     */
    public function testForGerman() {

        $this->markTestSkipped('SprintfLocker is disabled for now, we want to check if this is really needed. We Must be revisited.'); // TODO review

        $notAllowed = [
            '%-ige' => CTypeEnum::SPRINTF,
        ];

        $this->runTests($notAllowed, 'de-AT');
    }

    /**
     * @throws \Exception
     */
    public function testForHungarian() {

        $this->markTestSkipped('SprintfLocker is disabled for now, we want to check if this is really needed. We Must be revisited.'); // TODO review

        $notAllowed = [
            '%-xxx' => CTypeEnum::SPRINTF,
        ];

        $this->runTests($notAllowed, 'hu-HU');
    }

    /**
     * @throws \Exception
     */
    public function testForHebrew() {

        $this->markTestSkipped('SprintfLocker is disabled for now, we want to check if this is really needed. We Must be revisited.'); // TODO review

        $notAllowed = [
            '%s' => CTypeEnum::SPRINTF,
            '%u' => CTypeEnum::SPRINTF,
            '%d' => CTypeEnum::SPRINTF,
            '%c' => CTypeEnum::SPRINTF,
            '%x' => CTypeEnum::SPRINTF,
            '%@' => CTypeEnum::OBJECTIVE_C_NSSTRING,
        ];

        $this->runTests($notAllowed, 'he-IL');
    }

    /**
     * @throws \Exception
     */
    public function testForTurkish() {

        $this->markTestSkipped('SprintfLocker is disabled for now, we want to check if this is really needed. We Must be revisited.'); // TODO review

        $notAllowed = [
            '%s' => CTypeEnum::SPRINTF,
            '%u' => CTypeEnum::SPRINTF,
            '%d' => CTypeEnum::SPRINTF,
            '%c' => CTypeEnum::SPRINTF,
            '%x' => CTypeEnum::SPRINTF,
            '%@' => CTypeEnum::OBJECTIVE_C_NSSTRING,
        ];

        $this->runTests($notAllowed, 'tr-TR');
    }

    /**
     * @param $notAllowed
     * @param $languageToTest
     * @throws \Exception
     */
    private function runTests($notAllowed, $languageToTest)
    {
        foreach ($notAllowed as $placeholder => $ctype){



            $filter = MateCatFilter::getInstance( new FeatureSet(), 'en-US', $languageToTest );

            $segment   = "The house ".$placeholder." is red.";
            $segmentL1 = $filter->fromLayer0ToLayer1( $segment );
            $segmentL2 = $filter->fromLayer0ToLayer2( $segment );

            $this->assertEquals($segment, $segmentL1);
            $this->assertEquals($segment, $segmentL2);
        }
    }
}