<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class CtrlCharsPlaceHoldToAscii extends AbstractHandler {

    public function transform( $segment ) {

        //Replace br placeholders
        $segment = str_replace( ConstantEnum::crlfPlaceholder, "\r\n", $segment );
        $segment = str_replace( ConstantEnum::lfPlaceholder, "\n", $segment );
        $segment = str_replace( ConstantEnum::crPlaceholder, "\r", $segment );
        $segment = str_replace( ConstantEnum::tabPlaceholder, "\t", $segment );

        return $segment;

    }

}