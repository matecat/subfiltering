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
            '\[%\d+\$s\]',
            '\[%s:[a-z_]+\]',
            '\[%i\]',
            '\[%\d+\$i\]',
            '\[%i:[a-z_]+\]',
            '\[%f\]',
            '\[%.\d+f\]',
            '\[%\d+\$.\d+f\]',
            '\[%.\d+f:[a-z_]+\]',
            '\[%[a-z_]+:\d+%\]',
        ];

        $regex = '/'.implode("|", $tags).'/iu';

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