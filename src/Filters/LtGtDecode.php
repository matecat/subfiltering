<?php


namespace Matecat\SubFiltering\Filters;


use Matecat\SubFiltering\Commons\AbstractHandler;

class LtGtDecode extends AbstractHandler {

    public function transform( $segment ) {
        // restore < e >
        $segment = str_replace( "&lt;", "<", $segment );
        $segment = str_replace( "&gt;", ">", $segment );

        return $segment;
    }

}