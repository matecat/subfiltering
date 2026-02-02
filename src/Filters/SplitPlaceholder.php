<?php
/**
 * Created by PhpStorm.
 * User: vincenzoruffa
 * Date: 02/05/2019
 * Time: 17:20
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;
use Matecat\SubFiltering\Enum\ConstantEnum;

class SplitPlaceholder extends AbstractHandler
{
    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        return str_replace(ConstantEnum::splitPlaceHolder, "", $segment);
    }
}