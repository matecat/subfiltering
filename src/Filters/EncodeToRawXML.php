<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Utils\CatUtils;

class EncodeToRawXML extends AbstractHandler {
    public function transform( $segment ) {

        // handling &#10; (line feed)
        // prevent to convert it to \n
        $segment = preg_replace( '/&(#10;|#x0A;)|\n/', '##_ent_0A_##', $segment );

        // handling &#13; (carriage return)
        // prevent to convert it to \r
        $segment = preg_replace( '/&(#13;|#x0D;)|\r/', '##_ent_0D_##', $segment );

        // handling &#09; (tab)
        // prevent to convert it to \t
        $segment = preg_replace( '/&#09;|\t/', '##_ent_09_##', $segment );

        //Substitute 4(+)-byte characters from a UTF-8 string to htmlentities
        $segment = preg_replace_callback( '/([\xF0-\xF7]...)/s', [ CatUtils::class, 'htmlentitiesFromUnicode' ], $segment );

        // handling &#10;
        if ( strpos( $segment, '##_ent_0D_##' ) !== false ) {
            $segment = str_replace( '##_ent_0D_##', '&#13;', $segment );
        }

        // handling &#13;
        if ( strpos( $segment, '##_ent_0A_##' ) !== false ) {
            $segment = str_replace( '##_ent_0A_##', '&#10;', $segment );
        }

        // handling &#09; (tab)
        // prevent to convert it to \t
        if ( strpos( $segment, '##_ent_09_##' ) !== false ) {
            $segment = str_replace( '##_ent_09_##', '&#09;', $segment );
        }


        //encode all not valid XML entities
        $segment = preg_replace( '/&(?!lt;|gt;|amp;|quot;|apos;|#[x]{0,1}[0-9A-F]{1,7};)/', '&amp;', $segment );

        return $segment;
    }
}