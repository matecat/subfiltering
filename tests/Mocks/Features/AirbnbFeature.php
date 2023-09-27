<?php

namespace Matecat\SubFiltering\Tests\Mocks\Features;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Filters\HtmlToPh;
use Matecat\SubFiltering\Filters\SmartCounts;
use Matecat\SubFiltering\Filters\Variables;

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
        $channel->addAfter( new Variables(), new SmartCounts());

        return $channel;
    }
}