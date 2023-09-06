<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class RestoreXliffTagsInXliff extends AbstractHandler {

    /**
     * @inheritDoc
     */
    public function transform( $segment ) {
        $segment = str_replace( ConstantEnum::xliffInXliffStartPlaceHolder, "&lt;", $segment );
        $segment = str_replace( ConstantEnum::xliffInXliffEndPlaceHolder, "&gt;", $segment );

        return $segment;
    }
}
