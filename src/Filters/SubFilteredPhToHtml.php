<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class SubFilteredPhToHtml extends AbstractHandler {

    /**
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ) {
        // pipeline for restore PH tag of subfiltering to original encoded HTML
        preg_match_all( '|<ph id\s*=\s*["\']mtc_[0-9]+["\'] ctype\s*=\s*["\']x-([0-9a-zA-Z\-]+)["\'] equiv-text\s*=\s*["\']base64:([^"\']+)["\']\s*\/>|siU', $segment, $html, PREG_SET_ORDER ); // Ungreedy

        foreach ( $html as $subfilter_tag ) {

            /*
             * This code tries to handle xliff tags ( encoded ) inside a xliff.
             */
            $value   = base64_decode( $subfilter_tag[ 2 ] );
            $value   = $this->placeholdXliffTagsInXliff( $value );
            $segment = str_replace( $subfilter_tag[ 0 ], $value, $segment );

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
    private function placeholdXliffTagsInXliff( $value ) {
        $value = preg_replace( '#&lt;(g\s*id=["\']+.*?["\']+\s*(?!&lt;|&gt;)*?)&gt;#si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );

        $value = preg_replace( '|&lt;(/g)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );

        /*
         * According to the Oasis XLIFF standard, the X tags cannot be closing tags but only self-closing.
         * This regular expression does not comply with the oasis standard for xliff and is not required to do so, as it is an xliff embedded within an xliff in this case.
         * It is not Matecat's responsibility to handle user data sanitization.
         */
        $value = preg_replace( '|&lt;(/?x.*?/?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        
        $value = preg_replace( '#&lt;(bx[ ]{0,}/?|bx .*?/?)&gt;#si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '#&lt;(ex[ ]{0,}/?|ex .*?/?)&gt;#si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(bpt\s*.*?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/bpt)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(ept\s*.*?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/ept)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(ph .*?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/ph)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(ec .*?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/ec)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(sc .*?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/sc)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(pc .*?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/pc)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(it .*?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/it)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(mrk\s*.*?)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );
        $value = preg_replace( '|&lt;(/mrk)&gt;|si', ConstantEnum::xliffInXliffStartPlaceHolder . "$1" . ConstantEnum::xliffInXliffEndPlaceHolder, $value );

        return $value;
    }
}
