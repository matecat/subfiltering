<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;

class CtrlCharsPlaceHoldToAscii extends AbstractHandler {

    public function transform( $segment ) {

        //Replace br placeholders
        $segment = str_replace( Constants::crlfPlaceholder, "\r\n", $segment );
        $segment = str_replace( Constants::lfPlaceholder, "\n", $segment );
        $segment = str_replace( Constants::crPlaceholder, "\r", $segment );
        $segment = str_replace( Constants::tabPlaceholder, "\t", $segment );

        return $segment;

    }

}