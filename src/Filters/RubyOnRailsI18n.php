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

class RubyOnRailsI18n extends AbstractHandler {

    /**
     * Support for ruby on rails i18n variables
     *
     * TestSet:
     * <code>
     *  Dear %{person}, This is %{agent.alias} from Customer. %{ this will not locked } e %{ciao}
     * </code>
     *
     * @param $segment
     *
     * @return string
     */
    public function transform( $segment ) {
        preg_match_all( '/%{[^<>\s%]+?}/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $percentage_variable ) {
            //check if inside twig variable there is a tag because in this case shouldn't replace the content with PH tag
            if ( !strstr( $percentage_variable[ 0 ], ConstantEnum::GTPLACEHOLDER ) ) {
                //replace subsequent elements excluding already encoded
                $segment = preg_replace(
                        '/' . preg_quote( $percentage_variable[ 0 ], '/' ) . '/',
                        '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::RUBY_ON_RAILS . '" equiv-text="base64:' . base64_encode( $percentage_variable[ 0 ] ) . '"/>',
                        $segment,
                        1
                );
            }
        }

        return $segment;
    }

}