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

class StandardXEquivTextToMateCatCustomPH extends AbstractHandler
{

    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        preg_match_all('|<x[^>]*?equiv-text="([^"]*?)"[^>]*?/>|', $segment, $xTags, PREG_SET_ORDER);
        foreach ($xTags as $group) {
            $segment = preg_replace(
                '/' . preg_quote($group[0], '/') . '/',
                '<ph id="' . $this->getPipeline()->getNextId(
                ) . '" ctype="' . CTypeEnum::ORIGINAL_X->value . '" x-orig="' . base64_encode(
                    $group[0]
                ) . '" equiv-text="base64:' . base64_encode($group[1]) . '"/>',
                $segment,
                1
            );
        }

        return $segment;
    }


}


