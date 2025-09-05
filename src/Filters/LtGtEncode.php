<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;

class LtGtEncode extends AbstractHandler {

    public function transform( string $segment ): string {
        // restore < e >
        $segment = str_replace( "<", "&lt;", $segment );

        return str_replace( ">", "&gt;", $segment );
    }

}