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

class PlaceHoldXliffTags extends AbstractHandler
{

    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        // input : <g id="43">bang &amp; &lt; 3 olufsen </g>; <x id="33"/>

        //remove not existent </x> tags
        $segment = preg_replace('|(</x>)|si', "", $segment);

        $segment = $this->applyPlaceholders($segment);

        return preg_replace_callback(
            '/' . ConstantEnum::LTPLACEHOLDER->value . '(.*?)' . ConstantEnum::GTPLACEHOLDER->value . '/',
            function ($matches) {
                return ConstantEnum::LTPLACEHOLDER->value . base64_encode(
                        $matches[1]
                    ) . ConstantEnum::GTPLACEHOLDER->value;
            },
            $segment
        ); //base64 of the tag content to avoid unwanted manipulation
    }

    /**
     * @param string $segment
     * @return string
     */
    private function applyPlaceholders(string $segment): string
    {
        $patterns = [
            '|<(g\s*.*?)>|si',
            '|<(/g)>|si',
            '|<(x .*?/?)>|si',
            '#<(bx */?|bx .*?/?)>#si',
            '#<(ex */?|ex .*?/?)>#si',
            '|<(bpt\s*.*?)>|si',
            '|<(/bpt)>|si',
            '|<(ept\s*.*?)>|si',
            '|<(/ept)>|si',
            '|<(ph .*?)>|si',
            '|<(/ph)>|si',
            '|<(ec .*?)>|si',
            '|<(/ec)>|si',
            '|<(sc .*?)>|si',
            '|<(/sc)>|si',
            '|<(pc .*?)>|si',
            '|<(/pc)>|si',
            '|<(it .*?)>|si',
            '|<(/it)>|si',
            '|<(mrk\s*.*?)>|si',
            '|<(/mrk)>|si',
        ];
        foreach ($patterns as $pattern) {
            $segment = preg_replace(
                $pattern,
                ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
                $segment
            );
        }

        return $segment;
    }

}
