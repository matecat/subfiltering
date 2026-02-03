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

class SpecialEntitiesToPlaceholdersForView extends AbstractHandler
{
    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        return (string)str_ireplace(
            [
                '&#10;',
                '&#13;',
                ' ' /* NBSP in ascii value */,
                '&#x0A;',
                '&#x0C;',
                '&#160;',
                '&#xA0;',
                '&#09;',
                '&#9;',
                '&#x09;'
            ],
            [
                ConstantEnum::lfPlaceholder,
                ConstantEnum::crPlaceholder,
                ConstantEnum::nbspPlaceholder,
                ConstantEnum::lfPlaceholder,
                ConstantEnum::crPlaceholder,
                ConstantEnum::nbspPlaceholder,
                ConstantEnum::nbspPlaceholder,
                ConstantEnum::tabPlaceholder,
                ConstantEnum::tabPlaceholder,
                ConstantEnum::tabPlaceholder,
            ],
            $segment
        );
    }

}
