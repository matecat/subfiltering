<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class RemoveNBSPPlaceholder extends AbstractHandler {

    public function transform( $segment ) {

        return str_replace( ConstantEnum::nbspPlaceholder, "&nbsp;", $segment );
    }

}