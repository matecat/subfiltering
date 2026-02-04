<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;

class LtGtEncode extends AbstractHandler
{

    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        // restore < e >
        $segment = str_replace("<", "&lt;", $segment);

        return str_replace(">", "&gt;", $segment);
    }

}
