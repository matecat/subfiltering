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
use DomainException;

/**
 * A Java like Interface helper for key/value php arrays, with safe access to the elements (no warning for undefined index)
 *
 */
class ArrayList extends ArrayObject {

    /**
     * @param array $list
     */
    public function __construct( array $list = [] ) {
        if ( !empty( $list ) && !Utils::array_is_list( $list ) ) {
            throw new DomainException( "Invalid list provided" );
        }
        parent::__construct( $list );
    }

    public static function instance( array $list = [] ): ArrayList {
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

    /**
     * Appends the specified element to the end of this list.
     *
     * @param $value
     *
     * @return true
     */
    public function add( $value ): bool {
        parent::append( $value );
        return true;
    }

}