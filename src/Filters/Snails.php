<?php

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;

class Snails extends AbstractHandler
{
    /**
     * @inheritDoc
     */
    public function transform( $segment )
    {
        preg_match_all( '/@@[^<>\s%]+?@@|(?<!{)@[^<>\s@]+?@/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $snail_variable ) {
            //check if inside twig variable there is a tag because in this case shouldn't replace the content with PH tag
            if( !strstr($snail_variable[0], Constants::GTPLACEHOLDER) ){
                //replace subsequent elements excluding already encoded
                $segment = preg_replace(
                        '/' . preg_quote( $snail_variable[0], '/' ) . '/',
                        '<ph id="__mtc_' . $this->getPipeline()->getNextId() . '" equiv-text="base64:' . base64_encode( $snail_variable[ 0 ] ) . '"/>',
                        $segment,
                        1
                );
            }
        }

        return $segment;
    }
}