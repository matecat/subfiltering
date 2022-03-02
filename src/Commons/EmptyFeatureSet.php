<?php
/**
 * Created by PhpStorm.
 * @author ostico domenico@translated.net / ostico@gmail.com
 * Date: 02/03/22
 * Time: 18:39
 *
 */

namespace Matecat\SubFiltering\Commons;

use Matecat\SubFiltering\Contracts\FeatureSetInterface;

class EmptyFeatureSet implements FeatureSetInterface {

    /**
     * @inheritDoc
     */
    public function filter( $method, $filterable ) {
        return $filterable;
    }
}