<?php
/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 05/11/18
 * Time: 16.23
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class SpecialEntitiesToPlaceholdersForView extends AbstractHandler {

    public function transform( $segment ) {

        $segment = str_ireplace(
                [
                        '&#10;', '&#13;', ' ' /* NBSP in ascii value */,
                        '&#0A;', '&#0C;', '&#160;', '&#09;'
                ],
                [
                        ConstantEnum::lfPlaceholder,
                        ConstantEnum::crPlaceholder,
                        ConstantEnum::nbspPlaceholder,
                        ConstantEnum::lfPlaceholder,
                        ConstantEnum::crPlaceholder,
                        ConstantEnum::nbspPlaceholder,
                        ConstantEnum::tabPlaceholder
                ], $segment );

        return $segment;

    }

}