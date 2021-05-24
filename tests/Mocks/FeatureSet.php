<?php

namespace Matecat\SubFiltering\Tests\Mocks;

use Matecat\SubFiltering\Contracts\FeatureSetInterface;

class FeatureSet implements FeatureSetInterface
{
    /**
     * @inheritDoc
     */
    public function filter( $method, $filterable )
    {
        return $filterable;
    }
}