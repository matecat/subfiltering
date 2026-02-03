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
use Matecat\SubFiltering\Enum\CTypeEnum;

class StandardPHToMateCatCustomPH extends AbstractHandler
{
    public function transform(string $segment): string
    {
        $segment = $this->filterPhTagContent($segment);

        return $this->filterOriginalSelfClosePhTagsWithEquivText($segment);
    }

    /**
     * @param string $segment
     *
     * @return string
     */
    private function filterPhTagContent(string $segment): string
    {
        if (preg_match('|</ph>|s', $segment)) {
            preg_match_all('|<ph id=["\']([^\'"]+?)["\'].*?>(.*?)</ph>|', $segment, $phTags, PREG_SET_ORDER);
            foreach ($phTags as $group) {
                $segment = preg_replace(
                    '/' . preg_quote($group[0], '/') . '/',
                    '<ph id="' . $this->getPipeline()->getNextId(
                    ) . '" ctype="' . CTypeEnum::ORIGINAL_PH_CONTENT . '" x-orig="' . base64_encode(
                        $group[0]
                    ) . '" equiv-text="base64:' .
                    base64_encode(htmlentities($group[2], ENT_NOQUOTES | 16 /* ENT_XML1 */, 'UTF-8')) .
                    '"/>',
                    $segment,
                    1
                );
            }
        }

        return $segment;
    }

    /**
     * Show the equivalent text of the <ph> tag instead of the tag itself.
     *
     * @param string $segment
     *
     * @return string
     */
    private function filterOriginalSelfClosePhTagsWithEquivText(string $segment): string
    {
        preg_match_all(
            '|<ph[^>]+?equiv-text\s*?=\s*?(["\'])(?!base64:)(.*?)?\1[^>]*?/>|',
            $segment,
            $html,
            PREG_SET_ORDER
        );
        foreach ($html as $tag_attribute) {
            //replace subsequent elements excluding already encoded
            $segment = preg_replace(
                '/' . preg_quote($tag_attribute[0], '/') . '/',
                '<ph id="' . $this->getPipeline()->getNextId(
                ) . '" ctype="' . CTypeEnum::ORIGINAL_SELF_CLOSE_PH_WITH_EQUIV_TEXT . '" x-orig="' . base64_encode(
                    $tag_attribute[0]
                ) . '" equiv-text="base64:' .
                base64_encode(($tag_attribute[2] ?? '') != '' ? $tag_attribute[2] : 'NULL') .
                '"/>',
                $segment,
                1
            );
        }

        return $segment;
    }

}
