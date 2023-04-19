<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

class PercentSnail extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function transform( $segment )
    {
        preg_match_all( '/%@/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $percentSnailVariable ) {

            $segment = preg_replace(
                    '/' . preg_quote( $percentSnailVariable[0], '/' ) . '/',
                    '<ph id="__mtc_' . $this->getPipeline()->getNextId() . '" ctype="'.CTypeEnum::PERCENT_SNAILS.'" equiv-text="base64:' . base64_encode( $percentSnailVariable[ 0 ] ) . '"/>',
                    $segment,
                    1
            );
        }

        return $segment;
    }
}