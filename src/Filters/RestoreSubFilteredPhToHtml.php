<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 15.30
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;

class RestoreSubFilteredPhToHtml extends AbstractHandler {

    const matchPhRegexp = '|' . Constants::LTPLACEHOLDER . 'ph id\s*=\s*["\']mtc_[0-9]+["\'] equiv-text\s*=\s*["\']base64:([^"\']+)["\']\s*/' . Constants::GTPLACEHOLDER . '|siU';

    /**
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ){

        //pipeline for restore PH tag of subfiltering to original encoded HTML
        preg_match_all( self::matchPhRegexp, $segment, $html, PREG_SET_ORDER ); // Ungreedy
        foreach ( $html as $subfilter_tag ) {
            $segment = str_replace( $subfilter_tag[0], base64_decode( $subfilter_tag[ 1 ] ), $segment );
        }

        return $segment;

    }

}