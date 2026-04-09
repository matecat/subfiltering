<?php

namespace Matecat\SubFiltering\Contracts;

use Matecat\SubFiltering\Commons\Pipeline;

interface FeatureSetInterface
{
    public function customizeFromLayer0ToLayer1(Pipeline $pipeline): Pipeline;

    public function customizeFromLayer1ToLayer2(Pipeline $pipeline): Pipeline;

    public function customizeFromLayer2ToLayer1(Pipeline $pipeline): Pipeline;

    public function customizeFromRawXliffToLayer0(Pipeline $pipeline): Pipeline;

    public function customizeFromLayer0ToRawXliff(Pipeline $pipeline): Pipeline;

    public function customizeFromLayer1ToLayer0(Pipeline $pipeline): Pipeline;
}
