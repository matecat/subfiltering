<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

class PercentDoubleCurlyBrackets extends AbstractHandler
{

    /**
     * @param string $segment
     *
     * @return string
     */
    public function transform(string $segment): string
    {
        /*
         * Examples:
         * - %{{(text-align=center)}}
         */
        preg_match_all('/%{{(?!<ph )[^{}]*?}}/', $segment, $html, PREG_SET_ORDER);
        foreach ($html as $variable) {
            //replace subsequent elements excluding already encoded
            $segment = preg_replace(
                '/' . preg_quote($variable[0], '/') . '/',
                '<ph id="' . $this->getPipeline()->getNextId(
                ) . '" ctype="' . CTypeEnum::PERCENT_VARIABLE . '" equiv-text="base64:' . base64_encode(
                    $variable[0]
                ) . "\"/>",
                $segment,
                1
            );
        }

        return $segment;
    }
}