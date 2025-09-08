<?php
/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 02/09/25
 * Time: 18:07
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;
use Matecat\SubFiltering\Enum\CTypeEnum;

class ICUVariables extends AbstractHandler { // TODO

    /**
     * @inheritDoc
     */
    public function transform( string $segment ): string {
        preg_match_all( '/{.+}/', $segment, $text_content, PREG_SET_ORDER );
        foreach ( $text_content as $icu_variable ) {
            //check if inside the variable there is a tag because in this case shouldn't replace the content with PH tag
            if ( !strstr( $icu_variable[ 0 ], ConstantEnum::GTPLACEHOLDER ) ) {
                //replace subsequent elements excluding already encoded
                $segment = preg_replace(
                        '/' . preg_quote( $icu_variable[ 0 ], '/' ) . '/',
                        '<ph id="' . $this->getPipeline()->getNextId() . '" ctype="' . CTypeEnum::ICU . '" equiv-text="base64:' . base64_encode( $icu_variable[ 0 ] ) . '"/>',
                        $segment,
                        1
                );
            }
        }

        return $segment;
    }
}