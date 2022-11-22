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

class SubFilteredPhToHtml extends AbstractHandler {

    /**
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ){

        // pipeline for restore PH tag of subfiltering to original encoded HTML
        preg_match_all( '|<ph id\s*=\s*["\']mtc_[0-9]+["\'] equiv-text\s*=\s*["\']base64:([^"\']+)["\']\s*\/>|siU', $segment, $html, PREG_SET_ORDER ); // Ungreedy
        foreach ( $html as $subfilter_tag ) {
            $value = base64_decode( $subfilter_tag[ 1 ] );

            // @TODO issue of xliff tag inside xliff:
            // <source> &lt;g&gt; pippo &lt;/g&gt; </source>
            // here we know exactly that those xliff tags are encoded
            // we have to placehold them to avoid PlaceHoldXliffTags handle them as really xliff tag
            // we must add another layer after PlaceHoldXliffTags to restore the original values so the HtmlPlainTextDecoder can do it's job

            $value = html_entity_decode( $value, ENT_NOQUOTES | ENT_XML1 );
            $segment = str_replace( $subfilter_tag[0], $value, $segment );

        }

        return $segment;

    }

}