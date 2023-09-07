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
use Matecat\SubFiltering\Enum\ConstantEnum;

class RestorePlaceHoldersToXLIFFLtGt extends AbstractHandler {

    public function transform( $segment ) {
        $segment = str_replace( ConstantEnum::LTPLACEHOLDER, "<", $segment );
        $segment = str_replace( ConstantEnum::GTPLACEHOLDER, ">", $segment );

        return $segment;

    }

}