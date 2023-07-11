<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\CTypeEnum;

class SquareSprintf extends AbstractHandler {
    /**
     * @inheritDoc
     */
    public function transform( $segment ) {

        $tags = [
            '\[%s\]',
            '\[%1$s\]',
            '\[%s:name\]',
            '\[%i\]',
            '\[%1$i\]',
            '\[%i:name\]',
            '\[%f\]',
            '\[%.2f\]',
            '\[%1$.2f\]',
            '\[%.2f:name\]',
        ];

        $regex = '/'.implode("|", $tags).'/';

        preg_match_all( $regex, $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $percentSnailVariable ) {

            $segment = preg_replace(
                    '/' . preg_quote( $percentSnailVariable[ 0 ], '/' ) . '/',
                    '<ph id="__mtc_' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::SQUARE_SPRINTF . '" equiv-text="base64:' . base64_encode( $percentSnailVariable[ 0 ] ) . '"/>',
                    $segment,
                    1
            );
        }

        return $segment;
    }
}