<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

class SmartCounts extends AbstractHandler {
    /**
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ) {
        /*
         * Examples:
         * - [AIRBNB] Reminder: Reply to %{guest}’s inquiry. |||| [AIRBNB] Reminder: Reply to %{guest}’s inquiry.
         */
        preg_match_all( '/(\|\|\|\|)/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $variable ) {
            //replace subsequent elements excluding already encoded
            $segment = preg_replace(
                    '/' . preg_quote( $variable[ 0 ], '/' ) . '/',
                    '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::SMART_COUNT . '" equiv-text="base64:' . base64_encode( $variable[ 0 ] ) . "\"/>",
                    $segment,
                    1
            );
        }

        return $segment;
    }
}