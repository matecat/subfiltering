<?php

/**
 * Created by PhpStorm.
 * @author domenico domenico@translated.net / ostico@gmail.com
 * Date: 13/05/19
 * Time: 19.37
 *
 */

namespace Matecat\SubFiltering\Filters;

use Matecat\SubFiltering\Commons\AbstractHandler;

class RemoveDangerousChars extends AbstractHandler
{
    /**
     * @param string $segment
     * @return string
     */
    public function transform(string $segment): string
    {
        //clean invalid XML entities (characters with ascii < 32 and different from 0A, 0D and 09)
        $regexpHexEntity = '/&#x(0[0-8BCEF]|1[0-9A-F]|7F);/u';

        $regexpEntity = '/&#(0[0-8]|1[1-2]|1[4-9]|2[0-9]|3[0-1]|127);/u';

        //remove binary chars in some xliff files
        $regexpAscii = '/[\x{00}-\x{08}\x{0B}\x{0C}\x{0E}-\x{1F}\x{7F}]/u';

        $segment = preg_replace($regexpAscii, '', $segment);
        $segment = preg_replace($regexpHexEntity, '', $segment);

        return preg_replace($regexpEntity, '', $segment);
    }

}
