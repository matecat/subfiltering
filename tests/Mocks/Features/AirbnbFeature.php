<?php

namespace Matecat\SubFiltering\Tests\Mocks\Features;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Filters\SmartCounts;
use Matecat\SubFiltering\Filters\PercentDoubleCurlyBrackets;

class AirbnbFeature extends BaseFeature
{
    /**
     * Override default fromLayer0ToLayer1
     *
     * @param Pipeline $channel
     *
     * @return Pipeline
     */
    public function fromLayer0ToLayer1(Pipeline $channel)
    {
        $channel->addAfter( PercentDoubleCurlyBrackets::class, SmartCounts::class);

        return $channel;
    }
}