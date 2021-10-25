<?php

namespace Matecat\SubFiltering\Tests\Mocks\Features;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Filters\HtmlToPh;
use Matecat\SubFiltering\Filters\SingleCurlyBracketsToPh;
use Matecat\SubFiltering\Filters\TwigToPh;

class UberFeature extends BaseFeature
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
        $channel->addAfter( new HtmlToPh(), new SingleCurlyBracketsToPh() );
        $channel->remove(new TwigToPh());

        return $channel;
    }
}