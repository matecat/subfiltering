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
use Matecat\SubFiltering\Enum\CTypeEnum;

class RemoveCTypeFromPhTags extends AbstractHandler {

    public function transform( $segment )
    {
        preg_match_all( '|<ph id\s*=\s*["\'][a-zA-Z0-9]+["\'] ctype\s*=\s*["\']([^"\']+)["\'] equiv-text\s*=\s*["\']base64:([^"\']+)["\']\s*\/>|siU', $segment, $html, PREG_SET_ORDER );

        foreach ( $html as $subfilter_tag ) {
            if($subfilter_tag[1] === CTypeEnum::ORIGINAL_PH){
                $segment = str_replace( ' ctype="'.$subfilter_tag[1].'"', '', $segment );
                $segment = str_replace( 'base64:'.$subfilter_tag[ 2 ], base64_decode( $subfilter_tag[ 2 ] ), $segment );
            }
        }

        return $segment;
    }
}