<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 16.17
 *
 */

namespace Matecat\SubFiltering\Filters;


use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class RestoreXliffTagsContent extends AbstractHandler {

    public function transform( string $segment ): string {

        $segment = preg_replace_callback( '/' . ConstantEnum::LTPLACEHOLDER . '(.*?)' . ConstantEnum::GTPLACEHOLDER . '/u',
                function ( $matches ) {
                    $_match = base64_decode( $matches[ 1 ] );

                    return ConstantEnum::LTPLACEHOLDER . $_match . ConstantEnum::GTPLACEHOLDER;
                },
                $segment
        ); //base64 decode of the tag content to avoid unwanted manipulation

        return $segment;

    }

}