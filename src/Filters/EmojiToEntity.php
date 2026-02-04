<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 21/12/23
 * Time: 13:07
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\EmojiParser\Emoji;
use Matecat\SubFiltering\Commons\AbstractHandler;

class EmojiToEntity extends AbstractHandler
{

    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        return Emoji::toEntity($segment);
    }

}
