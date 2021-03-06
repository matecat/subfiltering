<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 16.23
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Utils\CatUtils;

class SpacesToNBSPForView extends AbstractHandler {

    public function transform( $segment ) {

        //replace all outgoing spaces couples to a space and a &nbsp; so they can be displayed to the browser
        $segment = preg_replace( '/[ ]{2}/', "&nbsp; ", $segment );
        $segment = preg_replace( '/[ ]$/', "&nbsp;", $segment );

        $segment = str_ireplace(
                [
                        '&#10;', '&#13;', ' ' /* NBSP in ascii value */,
                        '&#0A;', '&#0C;', '&#nbsp;'
                ],
                [
                        CatUtils::lfPlaceholder,
                        CatUtils::crPlaceholder,
                        CatUtils::nbspPlaceholder,
                        CatUtils::lfPlaceholder,
                        CatUtils::crPlaceholder,
                        CatUtils::nbspPlaceholder
                ], $segment );

        return $segment;

    }

}