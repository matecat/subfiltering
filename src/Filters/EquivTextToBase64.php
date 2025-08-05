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

class EquivTextToBase64 extends AbstractHandler {

    public function transform( $segment ) {

        // extract equiv-text attribute
        preg_match_all('/equiv-text=\"(.*?)\"/', $segment, $equiv_tags);

        if(!empty($equiv_tags[0])){
            foreach ($equiv_tags[0] as $index => $equiv_tag){
                $tag = $equiv_tags[1][$index];

                if (strpos($tag, "base64:") === false) {
                    $b = base64_encode($tag);
                    $segment = str_replace($equiv_tags[$index], 'equiv-text="base64:'.$b.'"', $segment);
                }
            }
        }

        return $segment;
    }
}
