<?php


namespace Matecat\SubFiltering\Filters;


use Matecat\SubFiltering\Commons\AbstractHandler;

class LtGtDoubleDecode extends AbstractHandler {

    public function transform( $segment ) {
        $segment = str_replace("&amp;lt;", "&lt;", $segment);
        $segment = str_replace("&amp;gt;", "&gt;", $segment);
        return $segment;
    }

}