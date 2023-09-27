<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;

class EncodeControlCharsInXliff extends AbstractHandler {

    /**
     * @inheritDoc
     */
    public function transform( $segment ) {
        return str_replace(
                [ "\r\n", "\r", "\n", "\t" ],
                [
                        '&#13;&#10;',
                        '&#13;',
                        '&#10;',
                        '&#09;'
                ], $segment );
    }
}
