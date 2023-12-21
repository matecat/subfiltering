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
use Matecat\SubFiltering\Utils\CatUtils;
use Matecat\XliffParser\Utils\Emoji;

/**
 * Class FromLayer2ToRawXML
 * Same as EncodeToRawXML but from strings coming from layer 2
 *
 * @package SubFiltering\Filters
 */
class FromLayer2ToRawXML extends AbstractHandler {

    private $brokenHTML = false;

    public function transform( $segment ) {

        // Filters BUG, segmentation on HTML, we should never get this at this level ( Should be fixed, anyway we try to cover )
//        $segment = $this->placeHoldBrokenHTML( $segment );

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
        $segment = str_replace( ConstantEnum::nbspPlaceholder, CatUtils::unicode2chr( 0Xa0 ), $segment );

        // Filters BUG, segmentation on HTML, we should never get this at this level ( Should be fixed, anyway we try to cover )
//        $segment = $this->resetBrokenHTML( $segment );

        return $segment;

    }

//    private function placeHoldBrokenHTML( $segment ) {
//
//        //Filters BUG, segmentation on HTML, we should never get this at this level ( Should be fixed, anyway we try to cover )
//        //    &lt;a href="/help/article/1381?
//        $this->brokenHTML = false;
//
//        //This is from Layer 2 to Layer 1
//        if ( stripos( $segment, '<a href="' ) ) {
//            $segment          = str_replace( '<a href="', '##__broken_lt__##a href=##__broken_quot__##', $segment );
//            $this->brokenHTML = true;
//        }
//
//        return $segment;
//
//    }
//
//    private function resetBrokenHTML( $segment ) {
//
//        // Reset
//        if ( $this->brokenHTML ) {
//            $segment = str_replace( '##__broken_lt__##a href=##__broken_quot__##', '&lt;a href="', $segment );
//        }
//
//        return $segment;
//
//    }

}