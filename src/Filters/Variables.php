<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

class Variables extends AbstractHandler {

    /**
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment )
    {
        /*
         * Examples:
         * - %{# }
         * - %{\n}$spaces=2%{\n}
         * - %{{(text-align=center)}}
         * - %{vars}
         * - [AIRBNB] Reminder: Reply to %{guest}’s inquiry. |||| [AIRBNB] Reminder: Reply to %{guest}’s inquiry.
         */
//        preg_match_all( '/%{{[^}]*?}}|(%{[^}]*?})[^\|]+?\1|(%{[^}]*?})/', $segment, $html, PREG_SET_ORDER );
        preg_match_all( '/%{{(?!<ph )[^}]*?}}|(%{(?!<ph )[^}]*?})/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $variable ) {
            //replace subsequent elements excluding already encoded
            $segment = preg_replace(
                    '/' . preg_quote( $variable[0], '/' ) . '/',
                    '<ph id="__mtc_' . $this->getPipeline()->getNextId() . '" ctype="'.CTypeEnum::PERCENT_VARIABLE.'" equiv-text="base64:' . base64_encode( $variable[ 0 ] ) . "\"/>",
                    $segment,
                    1
            );
        }

        return $segment;
    }
}