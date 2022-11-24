<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;

class SubFilteredPhToHtml extends AbstractHandler {

    /**
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment )
    {
        // pipeline for restore PH tag of subfiltering to original encoded HTML
        preg_match_all( '|<ph id\s*=\s*["\']mtc_[0-9]+["\'] equiv-text\s*=\s*["\']base64:([^"\']+)["\']\s*\/>|siU', $segment, $html, PREG_SET_ORDER ); // Ungreedy
        foreach ( $html as $subfilter_tag ) {
            $value = base64_decode( $subfilter_tag[ 1 ] );
            $value = $this->placeholdXliffTagsInXliff($value);
            $value = html_entity_decode( $value, ENT_NOQUOTES | ENT_XML1 );
            $segment = str_replace( $subfilter_tag[0], $value, $segment );

        }

        return $segment;
    }

    /**
     * Protect the xliff tag inside a xliff:
     *
     * Example: <source> &lt;g&gt; pippo &lt;/g&gt; </source>
     *
     * We have to placehold them to avoid PlaceHoldXliffTags handle them as really xliff tag.
     * Then we add another layer after PlaceHoldXliffTags to restore the original values so the HtmlPlainTextDecoder can do it's job.
     *
     * @param string $value
     *
     * @return mixed
     */
    private function placeholdXliffTagsInXliff($value)
    {
        $value = preg_replace( '|(&lt;/x&gt;)|si', "", $value );
        $value = preg_replace( '|&lt;(g\s*id=["\']+.*?["\']+\s*[^&lt;&gt;]*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );

        $value = preg_replace( '|&lt;(/g)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );

        $value = preg_replace( '|&lt;(x .*?/?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '#&lt;(bx[ ]{0,}/?|bx .*?/?)&gt;#si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '#&lt;(ex[ ]{0,}/?|ex .*?/?)&gt;#si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(bpt\s*.*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/bpt)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(ept\s*.*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/ept)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(ph .*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/ph)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(ec .*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/ec)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(sc .*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/sc)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(pc .*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/pc)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(it .*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/it)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(mrk\s*.*?)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/mrk)&gt;|si', Constants::xliffInXliffStartPlaceHolder . "$1" . Constants::xliffInXliffEndPlaceHolder, $value );

        return preg_replace_callback( '/' . Constants::xliffInXliffStartPlaceHolder . '(.*?)' . Constants::xliffInXliffEndPlaceHolder . '/u',
                function ( $matches ) {
                    return Constants::xliffInXliffStartPlaceHolder . $matches[ 1 ] . Constants::xliffInXliffEndPlaceHolder;
                }, $value
        );
    }
}
