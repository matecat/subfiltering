<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 16.17
 *
 */

namespace Matecat\SubFiltering\Filters;


use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;

class RestorePlaceHoldersToXLIFFLtGt extends AbstractHandler {

    public function transform( $segment ) {
        $segment = str_replace( Constants::LTPLACEHOLDER, "<", $segment );
        $segment = str_replace( Constants::GTPLACEHOLDER, ">", $segment );

        return $segment;

    }

}