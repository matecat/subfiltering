<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class CtrlCharsPlaceHoldToAscii extends AbstractHandler
{

    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        //Replace br placeholders
        $segment = str_replace(ConstantEnum::crlfPlaceholder, "\r\n", $segment);
        $segment = str_replace(ConstantEnum::lfPlaceholder, "\n", $segment);
        $segment = str_replace(ConstantEnum::crPlaceholder, "\r", $segment);

        return str_replace(ConstantEnum::tabPlaceholder, "\t", $segment);
    }

}