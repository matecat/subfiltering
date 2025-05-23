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
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

class TwigToPh extends AbstractHandler {

    /**
     * TestSet:
     * <code>
     *  Dear {{%%customer.first_name%%}}, This is {{ "now"|date(null, "Europe/Rome") }} with {%%agent.alias%%} Airbnb. {% for user in users %} e {%%ciao%%}
     *  {# note: disabled template because we no longer use this
     *          {% for user in users %}
     *          ...
     *          {% endfor %}
     *
     * ... {variable}
     *  #}
     * </code>
     *
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ) {
        preg_match_all( '/{{[^<>!{}]+?}}|{%[^<>!%]+?%}|{#[^<>!#]+?#}/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $twig_variable ) {
            //check if inside twig variable there is a tag because in this case shouldn't replace the content with PH tag
            if ( !strstr( $twig_variable[ 0 ], ConstantEnum::GTPLACEHOLDER ) ) {
                //replace subsequent elements excluding already encoded
                $segment = preg_replace(
                        '/' . preg_quote( $twig_variable[ 0 ], '/' ) . '/',
                        '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::TWIG . '" equiv-text="base64:' . base64_encode( $twig_variable[ 0 ] ) . '"/>',
                        $segment,
                        1
                );
            }
        }

        return $segment;
    }

}