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

class PlaceHoldXliffTags extends AbstractHandler {

    public function transform( string $segment ): string {

        // input : <g id="43">bang &amp; &lt; 3 olufsen </g>; <x id="33"/>

        //remove not existent </x> tags
        $segment = preg_replace( '|(</x>)|si', "", $segment );

        $segment = preg_replace( '|<(g\s*.*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/g)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );

        $segment = preg_replace( '|<(x .*?/?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '#<(bx[ ]{0,}/?|bx .*?/?)>#si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '#<(ex[ ]{0,}/?   |ex .*?/?)>#si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(bpt\s*.*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/bpt)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(ept\s*.*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/ept)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(ph .*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/ph)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(ec .*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/ec)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(sc .*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/sc)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(pc .*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/pc)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(it .*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/it)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(mrk\s*.*?)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );
        $segment = preg_replace( '|<(/mrk)>|si', ConstantEnum::LTPLACEHOLDER . "$1" . ConstantEnum::GTPLACEHOLDER, $segment );

        return preg_replace_callback( '/' . ConstantEnum::LTPLACEHOLDER . '(.*?)' . ConstantEnum::GTPLACEHOLDER . '/u',
                function ( $matches ) {
                    return ConstantEnum::LTPLACEHOLDER . base64_encode( $matches[ 1 ] ) . ConstantEnum::GTPLACEHOLDER;
                }, $segment
        ); //base64 of the tag content to avoid unwanted manipulation
    }
}