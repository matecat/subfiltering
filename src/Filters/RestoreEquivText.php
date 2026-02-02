<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 13/05/19
 * Time: 19.37
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;

class RestoreEquivText extends AbstractHandler
{
    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        // extract equiv-text attribute
        preg_match_all('/equiv-text=\"(.*?)\"/', $segment, $equiv_tags);

        if (!empty($equiv_tags[0])) {
            foreach ($equiv_tags[0] as $index => $equiv_tag) {
                $tag = $equiv_tags[1][$index];

                if (str_contains($tag, "base64:")) {
                    $b = base64_decode(str_replace("base64:", "", $tag));
                    $segment = str_replace($equiv_tags[$index], 'equiv-text="' . $b . '"', $segment);
                }
            }
        }

        return $segment;
    }
}
