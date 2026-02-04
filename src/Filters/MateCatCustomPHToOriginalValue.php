<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 11/01/19
 * Time: 15.11
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;

class MateCatCustomPHToOriginalValue extends AbstractHandler
{

    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        $segment = $this->restoreOriginalTags($segment);

        return $this->restoreFilteredContent($segment);
    }

    /**
     * This pipeline method is needed to restore x-original content (when an entire tag is replaced with a PH)
     *
     * @param string $segment
     *
     * @return string
     */
    private function restoreOriginalTags(string $segment): string
    {
        preg_match_all(
            '|<ph id\s*=\s*["\']mtc_\d+["\'] ctype\s*=\s*["\']([^"\']+)["\'] x-orig\s*=\s*["\']([^"\']+)["\'] equiv-text\s*=\s*["\']base64:[^"\']+["\']\s*/>|iU',
            $segment,
            $html,
            PREG_SET_ORDER
        ); // Ungreedy
        foreach ($html as $subfilter_tag) {
            $segment = str_replace($subfilter_tag[0], base64_decode($subfilter_tag[2]), $segment);
        }

        return $segment;
    }

    /**
     * This pipeline method is needed to restore a filtered block (as PH tag) to its original value.
     * It can be everything filtered out: sprintf, html, etc.
     *
     * @param string $segment
     *
     * @return string
     */
    private function restoreFilteredContent(string $segment): string
    {
        // pipeline for restore PH tag of subfiltering to original encoded HTML
        preg_match_all(
            '|<ph id\s*=\s*["\']mtc_\d+["\'] ctype\s*=\s*["\']([\w\-]+)["\'] equiv-text\s*=\s*["\']base64:([^"\']+)["\']\s*/>|iU',
            $segment,
            $html,
            PREG_SET_ORDER
        ); // Ungreedy

        foreach ($html as $subfilter_tag) {
            /*
             * This code tries to handle xliff/html tags (encoded) inside an xliff.
             */
            $value = base64_decode($subfilter_tag[2]);
            $segment = str_replace($subfilter_tag[0], $value, $segment);
        }

        return $segment;
    }

}