<?php

namespace Matecat\SubFiltering\Tests\Mocks;

use Matecat\SubFiltering\Commons\Pipeline;
use Matecat\SubFiltering\Contracts\FeatureSetInterface;
use Matecat\SubFiltering\Tests\Mocks\Features\BaseFeature;

class FeatureSet implements FeatureSetInterface
{
    /**
     * @var BaseFeature[]
     */
    private array $features = [];

    /**
     * FeatureSet constructor.
     *
     * @param BaseFeature[] $features
     */
    public function __construct(?array $features = null)
    {
        if (!empty($features)) {
            $this->features = $features;
        }
    }

    public function customizeFromLayer0ToLayer1(Pipeline $pipeline): Pipeline
    {
        return $this->customize('fromLayer0ToLayer1', $pipeline);
    }

    public function customizeFromLayer1ToLayer2(Pipeline $pipeline): Pipeline
    {
        return $this->customize('fromLayer1ToLayer2', $pipeline);
    }

    public function customizeFromLayer2ToLayer1(Pipeline $pipeline): Pipeline
    {
        return $this->customize('fromLayer2ToLayer1', $pipeline);
    }

    public function customizeFromRawXliffToLayer0(Pipeline $pipeline): Pipeline
    {
        return $this->customize('fromRawXliffToLayer0', $pipeline);
    }

    public function customizeFromLayer0ToRawXliff(Pipeline $pipeline): Pipeline
    {
        return $this->customize('fromLayer0ToRawXliff', $pipeline);
    }

    public function customizeFromLayer1ToLayer0(Pipeline $pipeline): Pipeline
    {
        return $this->customize('fromLayer1ToLayer0', $pipeline);
    }

    private function customize(string $hookName, Pipeline $pipeline): Pipeline
    {
        foreach ($this->features as $feature) {
            /* @var $feature BaseFeature */

            if (!is_null($feature) && method_exists($feature, $hookName)) {
                $pipeline = $feature->{$hookName}($pipeline);
            }
        }

        return $pipeline;
    }
}
