<?php
/**
 * Created by PhpStorm.
 * @author Domenico Lupinetti (hashashiyyin) domenico@translated.net / ostico@gmail.com
 * Date: 02/02/26
 * Time: 17:45
 *
 */

namespace Matecat\SubFiltering\Utils;

interface ListInterface
{
    /**
     * @param array<int,mixed> $list
     */
    public function __construct(array $list = []);

}