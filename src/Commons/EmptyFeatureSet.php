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

/**
 * Used from sources which want not to implement a custom object from this package
 */
class EmptyFeatureSet implements FeatureSetInterface {

    /**
     * @inheritDoc
     */
    public function filter( $method, $filterable ) {
        return $filterable;
    }
}