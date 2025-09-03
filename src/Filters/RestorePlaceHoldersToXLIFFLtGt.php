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
use Matecat\SubFiltering\Enum\CTypeEnum;

class RestorePlaceHoldersToXLIFFLtGt extends AbstractHandler {

    public function transform(string $segment ): string {
        $segment = str_replace( ConstantEnum::LTPLACEHOLDER, "<", $segment );

        return str_replace( ConstantEnum::GTPLACEHOLDER, ">", $segment );
    }
}
