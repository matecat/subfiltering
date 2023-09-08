<?php


namespace Matecat\SubFiltering\Filters;


use Matecat\SubFiltering\Commons\AbstractHandler;

class NBSPDoubleEncode extends AbstractHandler {

    public function transform( $segment ) {
        $segment = str_replace( "&nbsp;", "&amp;nbsp;", $segment );

        return $segment;
    }

}