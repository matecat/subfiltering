<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class RestoreNBSPPlaceholders extends AbstractHandler {

    public function transform( $segment ) {

        return str_replace( [ "&nbsp;" ], ConstantEnum::nbspPlaceholder, $segment );
    }

}