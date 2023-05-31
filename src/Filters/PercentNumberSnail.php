<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

class PercentNumberSnail extends AbstractHandler {
    /**
     * @inheritDoc
     */
    public function transform( $segment ) {

        preg_match_all( '/%\d+\$@/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $percentNumberSnailVariable ) {

            $segment = preg_replace(
                    '/' . preg_quote( $percentNumberSnailVariable[ 0 ], '/' ) . '/',
                    '<ph id="__mtc_' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::PERCENT_NUMBER_SNAILS . '" equiv-text="base64:' . base64_encode( $percentNumberSnailVariable[ 0 ] ) . '"/>',
                    $segment,
                    1
            );
        }

        return $segment;
    }
}