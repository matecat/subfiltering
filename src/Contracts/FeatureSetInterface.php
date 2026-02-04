<?php

namespace Matecat\SubFiltering\Contracts;

use Exception;

interface FeatureSetInterface
{
    /**
     * Returns the filtered subject variable passed to all enabled features.
     *
     * @param string $method
     * @param mixed $filterable
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function filter(string $method, mixed $filterable): mixed;
}
