<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

class Snails extends AbstractHandler {
    /**
     * @inheritDoc
     */
    public function transform( $segment ) {
        preg_match_all( '/@@[^<>\s]+?@@/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $snail_variable ) {
            //check if inside twig variable there is a tag because in this case shouldn't replace the content with PH tag
            if ( !strstr( $snail_variable[ 0 ], ConstantEnum::GTPLACEHOLDER ) ) {
                //replace subsequent elements excluding already encoded
                $segment = preg_replace(
                        '/' . preg_quote( $snail_variable[ 0 ], '/' ) . '/',
                        '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::SNAILS . '" equiv-text="base64:' . base64_encode( $snail_variable[ 0 ] ) . '"/>',
                        $segment,
                        1
                );
            }
        }

        return $segment;
    }
}