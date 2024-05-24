<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 24/05/24
 * Time: 18:25
 *
 */

namespace Matecat\SubFiltering\Utils;

use ArrayObject;

class ArrayList extends ArrayObject {

    /**
     * @param array $list
     */
    public function __construct( array $list = [] ) {
        parent::__construct( $list );
    }

    public static function instance( array $list = [] ) {
        return new static( $list );
    }

    /**
     * @param $key
     *
     * @return false|mixed|null
     */
    public function offsetGet( $key ) {
        if ( $this->offsetExists( $key ) ) {
            return parent::offsetGet( $key );
        }

        return null;
    }

    /**
     * Returns the element at the specified position in this list.
     *
     * @param $key
     *
     * @return false|mixed|null the element at the specified position in this list
     */
    public function get( $key ) {
        return $this->offsetGet( $key );
    }


}