<?php

namespace Matecat\SubFiltering\Contracts;

interface FeatureSetInterface {
    /**
     * Returns the filtered subject variable passed to all enabled features.
     *
     * @param $method
     * @param $filterable
     *
     * @return mixed
     *
     * FIXME: this is not a real filter since the input params are not passed
     * modified in cascade to the next function in the queue.
     * @throws \Exception
     */
    public function filter( $method, $filterable );
}