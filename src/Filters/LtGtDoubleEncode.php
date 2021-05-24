<?php


namespace Matecat\SubFiltering\Filters;


use Matecat\SubFiltering\Commons\AbstractHandler;

class LtGtDoubleEncode extends AbstractHandler {

    public function transform( $segment ) {
        $segment = str_replace("&lt;", "&amp;lt;", $segment);
        $segment = str_replace("&gt;", "&amp;gt;", $segment);
        return $segment;
    }

}