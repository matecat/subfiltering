<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;

class RestoreXliffTagsInXliff extends AbstractHandler {

    /**
     * @inheritDoc
     */
    public function transform( $segment )
    {
        $segment = str_replace( Constants::xliffInXliffStartPlaceHolder, "&lt;", $segment );
        $segment = str_replace( Constants::xliffInXliffEndPlaceHolder, "&gt;", $segment );

        return $segment;
    }
}
