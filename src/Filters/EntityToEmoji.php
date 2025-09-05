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

class EntityToEmoji extends AbstractHandler {

    public function transform( string $segment ): string {
        return Emoji::toEmoji( $segment );
    }


}