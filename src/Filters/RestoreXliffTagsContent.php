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

class RestoreXliffTagsContent extends AbstractHandler
{
    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        //base64 decode of the tag content to avoid unwanted manipulation
        return preg_replace_callback(
            '/' . ConstantEnum::LTPLACEHOLDER->value . '(.*?)' . ConstantEnum::GTPLACEHOLDER->value . '/u',
            function ($matches) {
                $_match = base64_decode($matches[1]);

                return ConstantEnum::LTPLACEHOLDER->value . $_match . ConstantEnum::GTPLACEHOLDER->value;
            },
            $segment
        );
    }

}