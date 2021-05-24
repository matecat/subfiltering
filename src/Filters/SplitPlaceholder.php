<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 02/05/2019
 * Time: 17:20
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;

class SplitPlaceholder extends AbstractHandler {
    public function transform( $segment ) {
        $segment = str_replace( Constants::splitPlaceHolder, "", $segment );

        return $segment;
    }
}