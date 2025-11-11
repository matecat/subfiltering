<?php

namespace Matecat\SubFiltering\Contracts;

use Exception;

interface FeatureSetInterface {
    /**
     * Returns the filtered subject variable passed to all enabled features.
     *
     * @param $method
     * @param $filterable
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function filter( $method, $filterable );
}