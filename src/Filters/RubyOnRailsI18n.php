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

class RubyOnRailsI18n extends AbstractHandler {

    const DOUBLE_CURLY_BRACKETS_PROTECT_START_TAG = '######__DOUBLE_CURLY_BRACKETS_START__######';
    const DOUBLE_CURLY_BRACKETS_PROTECT_END_TAG = '######__DOUBLE_CURLY_BRACKETS_END__######';

    /**
     * Support for ruby on rails i18n variables
     *
     * TestSet:
     * <code>
     *  Dear %{person}, This is %{agent.alias} from Customer. %{ this will not locked } e %{ciao}
     * </code>
     *
     * This special syntax:
     *
     * {{|placeholder|}}
     *
     * is not processed by this filter
     *
     * @param $segment
     * @return string
     */
    public function transform( $segment ) {

        // protect ​%{{|placeholder|}}
        preg_match_all('/​%{{\|\w+\|}}/', $segment, $doubleCurlyBracketRecords, PREG_SET_ORDER);
        foreach ($doubleCurlyBracketRecords as $pos => $doubleCurlyBracketRecord){
            $protectedTag = self::DOUBLE_CURLY_BRACKETS_PROTECT_START_TAG . base64_encode($doubleCurlyBracketRecord[0]) . self::DOUBLE_CURLY_BRACKETS_PROTECT_END_TAG;
            $segment = str_replace($doubleCurlyBracketRecord[0], $protectedTag, $segment);
        }

        preg_match_all( '/%{[^<>\s%]+?}/', $segment, $html, PREG_SET_ORDER );
        foreach ( $html as $pos => $percentage_variable ) {
            //check if inside twig variable there is a tag because in this case shouldn't replace the content with PH tag
            if( !strstr($percentage_variable[0], Constants::GTPLACEHOLDER) ){
                //replace subsequent elements excluding already encoded
                $segment = preg_replace(
                        '/' . preg_quote( $percentage_variable[0], '/' ) . '/',
                        '<ph id="__mtc_' . $this->getPipeline()->getNextId() . '" equiv-text="base64:' . base64_encode( $percentage_variable[ 0 ] ) . '"/>',
                        $segment,
                        1
                );
            }
        }

        // unprotect ​%{{|discount|}}
        preg_match_all('/'.self::DOUBLE_CURLY_BRACKETS_PROTECT_START_TAG.'\w+'.self::DOUBLE_CURLY_BRACKETS_PROTECT_END_TAG.'/', $segment, $protectedDoubleCurlyBracketRecords, PREG_SET_ORDER);
        foreach ($protectedDoubleCurlyBracketRecords as $pos => $protectedDoubleCurlyBracketRecord){
            $unprotectedTag = str_replace([self::DOUBLE_CURLY_BRACKETS_PROTECT_START_TAG, self::DOUBLE_CURLY_BRACKETS_PROTECT_END_TAG], '', $protectedDoubleCurlyBracketRecord[0]);
            $unprotectedTag = base64_decode($unprotectedTag);
            $segment = str_replace($protectedDoubleCurlyBracketRecord[0], $unprotectedTag, $segment);
        }

        return $segment;
    }

}