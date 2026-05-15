<?php

namespace Matecat\SubFiltering\Events;

use Matecat\SubFiltering\Commons\Pipeline;

class FromLayer2ToLayer1Event
{
    public function __construct(private Pipeline $pipeline)
    {
    }

    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }

    public function setPipeline(Pipeline $pipeline): void
    {
        $this->pipeline = $pipeline;
    }
}
