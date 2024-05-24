<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 18.47
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Utils\Utils;

/**
 * Class FromLayer2ToRawXML
 * Same as EncodeToRawXML but from strings coming from layer 2
 *
 * @package SubFiltering\Filters
 */
class FromLayer2ToRawXML extends AbstractHandler {

    private $brokenHTML = false;

    public function transform( $segment ) {

        //normal control characters must be converted to entities
        $segment = str_replace(
                [ "\r\n", "\r", "\n", "\t", "", ],
                [
                        '&#13;&#10;',
                        '&#13;',
                        '&#10;',
                        '&#09;',
                        '&#157;',
                ], $segment );

        // now convert the real &nbsp;
        return str_replace( ConstantEnum::nbspPlaceholder, Utils::unicode2chr( 0Xa0 ), $segment );

    }

}