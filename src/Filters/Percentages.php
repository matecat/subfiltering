<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 17.34
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Commons\Constants;
use Matecat\SubFiltering\Enum\CTypeEnum;

class Percentages extends AbstractHandler {

    /**
     * All inside percentages will be locked if there are no spaces
     *
     * TestSet:
     * <code>
     *  Dear %%customer.first_name%%, This is %agent.alias%% from Skyscanner. % this-will-not-locked % e %%ciao%% a {%this-will-not-locked%
     * </code>
     *
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ) {
        preg_match_all( '/%%[^<>\s%]+?%%/', $segment, $html, PREG_SET_ORDER ); // removed single percentage support '/(?<!{)%[^<>\s%]+?%/'
        foreach ( $html as $pos => $percentage_variable ) {
            //check if inside twig variable there is a tag because in this case shouldn't replace the content with PH tag
            if ( !strstr( $percentage_variable[ 0 ], Constants::GTPLACEHOLDER ) ) {
                //replace subsequent elements excluding already encoded
                $segment = preg_replace(
                        '/' . preg_quote( $percentage_variable[ 0 ], '/' ) . '/',
                        '<ph id="__mtc_' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::PERCENTAGES . '" equiv-text="base64:' . base64_encode( $percentage_variable[ 0 ] ) . '"/>',
                        $segment,
                        1
                );
            }
        }

        return $segment;
    }

}