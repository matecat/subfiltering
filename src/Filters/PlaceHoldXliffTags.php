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

        $segment = preg_replace(
            '|<(g\s*.*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/g)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );

        $segment = preg_replace(
            '|<(x .*?/?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '#<(bx */?|bx .*?/?)>#si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '#<(ex */?|ex .*?/?)>#si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(bpt\s*.*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/bpt)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(ept\s*.*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/ept)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(ph .*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/ph)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(ec .*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/ec)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(sc .*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/sc)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(pc .*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/pc)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(it .*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/it)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(mrk\s*.*?)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );
        $segment = preg_replace(
            '|<(/mrk)>|si',
            ConstantEnum::LTPLACEHOLDER->value . "$1" . ConstantEnum::GTPLACEHOLDER->value,
            $segment
        );

        return preg_replace_callback(
            '/' . ConstantEnum::LTPLACEHOLDER->value . '(.*?)' . ConstantEnum::GTPLACEHOLDER->value . '/',
            function ($matches) {
                return ConstantEnum::LTPLACEHOLDER->value . base64_encode($matches[1]) . ConstantEnum::GTPLACEHOLDER->value;
            },
            $segment
        ); //base64 of the tag content to avoid unwanted manipulation
    }
}