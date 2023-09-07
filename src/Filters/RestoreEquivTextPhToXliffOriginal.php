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
use Matecat\SubFiltering\Enum\ConstantEnum;

class RestoreEquivTextPhToXliffOriginal extends AbstractHandler {

    /**
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ) {

        //pipeline to convert back XLIFF PH to the original ones
        preg_match_all( '/' . ConstantEnum::LTPLACEHOLDER . 'ph id\s*=\s*[\'"](?!mtc_).*?[\'"] ctype="x-twig" equiv-text\s*=\s*[\'"]base64:([^"\']+?)[\'"]\s*\/' . ConstantEnum::GTPLACEHOLDER . '/', $segment, $html, PREG_SET_ORDER ); // Ungreedy
        foreach ( $html as $tag_attribute ) {
            $segment = str_replace( "base64:" . $tag_attribute[ 1 ], base64_decode( $tag_attribute[ 1 ] ), $segment );
        }

        return $segment;

    }

}