<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;

class LtGtEncode extends AbstractHandler {

    public function transform( $segment ) {
        // restore < e >
        $segment = str_replace("<", "&lt;", $segment);
        $segment = str_replace(">", "&gt;", $segment);
        $segment = str_replace(Constants::ENCODED_LTPLACEHOLDER,"&lt;", $segment);
        $segment = str_replace(Constants::ENCODED_GTPLACEHOLDER, "&gt;", $segment);

        return $segment;
    }

}