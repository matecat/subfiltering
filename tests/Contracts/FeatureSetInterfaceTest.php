<?php
declare(strict_types=1);

namespace Matecat\SubFiltering\Tests\Contracts;

use Matecat\SubFiltering\Commons\EmptyFeatureSet;
use Matecat\SubFiltering\Commons\Pipeline;
use PHPUnit\Framework\TestCase;

class FeatureSetInterfaceTest extends TestCase
{
    public function testEmptyFeatureSetReturnsUnmodifiedPipeline(): void
    {
        $featureSet = new EmptyFeatureSet();
        $pipeline = new Pipeline('en-US', 'it-IT');

        $this->assertSame($pipeline, $featureSet->customizeFromLayer0ToLayer1($pipeline));
        $this->assertSame($pipeline, $featureSet->customizeFromLayer1ToLayer2($pipeline));
        $this->assertSame($pipeline, $featureSet->customizeFromLayer2ToLayer1($pipeline));
        $this->assertSame($pipeline, $featureSet->customizeFromRawXliffToLayer0($pipeline));
        $this->assertSame($pipeline, $featureSet->customizeFromLayer0ToRawXliff($pipeline));
        $this->assertSame($pipeline, $featureSet->customizeFromLayer1ToLayer0($pipeline));
    }
}
