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
        $segment = str_replace(ConstantEnum::crlfPlaceholder->value, "\r\n", $segment);
        $segment = str_replace(ConstantEnum::lfPlaceholder->value, "\n", $segment);
        $segment = str_replace(ConstantEnum::crPlaceholder->value, "\r", $segment);

        return str_replace(ConstantEnum::tabPlaceholder->value, "\t", $segment);
    }

}
