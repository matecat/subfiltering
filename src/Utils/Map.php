<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 24/05/24
 * Time: 13:41
 *
 */

namespace Matecat\SubFiltering\Utils;

use ArrayAccess;
use ArrayIterator;
use Generator;
use IteratorAggregate;

/**
 * Java like interface helper for key/value php arrays
 *
 */
class Map implements IteratorAggregate, ArrayAccess {

    /**
     * @var array
     */
    private $map;

    public function __construct( array $map ) {
        $this->map = $map;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator( $this->map );
    }

    /**
     * @param $offset
     *
     * @return bool
     */
    public function offsetExists( $offset ) {
        return array_key_exists( $offset, $this->map );
    }

    /**
     * @param $offset
     *
     * @return mixed|null
     */
    public function offsetGet( $offset ) {
        return $this->get( $offset );
    }

    /**
     * @param $offset
     * @param $value
     *
     * @return void
     */
    public function offsetSet( $offset, $value ) {
        $this->put( $offset, $value );
    }

    /**
     * @param $offset
     *
     * @return void
     */
    public function offsetUnset( $offset ) {
        $this->remove( $offset );
    }


    /**
     * @return Generator
     */
    public function entrySet() {
        foreach ( $this->map as $k => $v ) {
            yield [ $k, $v ];
        }
    }

    public static function instance( array $map = [] ) {
        return new static( $map );
    }

    /**
     * Safe key access. Avoid warnings.
     *
     * @param       $needle
     *
     * @return mixed|null
     */
    public function get( $needle ) {
        return $this->getOrDefault( $needle, null );
    }

    /**
     * @param $needle
     * @param $default
     *
     * @return mixed
     */
    public function getOrDefault( $needle, $default ) {
        return array_key_exists( $needle, $this->map ) ? $this->map [ $needle ] : $default;
    }

    /**
     * Removes all the mappings from this map.
     * @return void
     */
    public function clear() {
        $this->map = [];
    }

    /**
     * Returns a shallow copy of this HashMap instance: the keys and values themselves are not cloned.
     * @return $this
     */
    public function __clone() {
        return new static( $this->map );
    }

    public function containsKey( $key ) {
        return array_key_exists( $key, $this->map );
    }

    /**
     * Returns true if this map maps one or more keys to the specified value.
     * If the value is found more than once, the first matching key is returned.
     *
     * @param $value
     *
     * @return false|string
     */
    public function containsValue( $value ) {
        return array_search( $value, $this->map, true );
    }

    /**
     * Performs the given action for each entry in this map until all entries have been processed or the action throws an exception.
     * Actions are performed in the order of entry set iteration.
     *
     * @param callable $callable
     *
     * @return Generator
     */
    public function foreEach( callable $callable ) {
        foreach ( $this->map as $k => $v ) {
            yield $callable( $k, $v );
        }
    }

    /**
     * Returns true if this map contains no key-value mappings.
     *
     * @return bool
     */
    public function isEmpty() {
        return empty( $this->map );
    }

    /**
     * @return Map
     */
    public function keySet() {
        return static::instance( array_keys( $this->map ) );
    }

    /**
     * Associates the specified value with the specified key in this map. If the map previously contained a mapping for the key, the old value is replaced.
     *
     * @param $key
     * @param $value
     *
     * @return mixed|null the previous value associated with `key`, or null if there was no mapping for `key`.
     */
    public function put( $key, $value ) {
        $previousValue     = $this->get( $key );
        $this->map[ $key ] = $value;

        return $previousValue;
    }

    /**
     * @param $map
     *
     * @return void
     */
    public function putAll( $map ) {
        foreach ( $map as $k => $v ) {
            $this->map[ $k ] = $v;
        }
    }

    /**
     * If the specified key is not already associated with a value (or is mapped to null) associates it with the given value and returns null, else returns the current value.
     *
     * @param $key
     * @param $value
     *
     * @return mixed|null the previous value associated with the specified key, or null if there was no mapping for the key.
     *               A null return can also indicate that the map previously associated null with the `key`
     */
    public function putIfAbsent( $key, $value ) {
        $previousValue = $this->get( $key );
        if ( $previousValue === null ) {
            $this->map[ $key ] = $value;
        }

        return $previousValue;
    }

    /**
     * Removes the mapping for the specified key from this map if present.
     *
     * @param $key
     *
     * @return bool true if the value was removed
     */
    public function remove( $key ) {
        $exists = array_key_exists( $key, $this->map );
        if ( $exists ) {
            unset( $this->map[ $key ] );
        }

        return $exists;
    }

    /**
     * Replaces the entry for the specified key only if it is currently mapped to some value.
     *
     * @return mixed|null the previous value associated with the specified key, or null if there was no mapping for the key.
     *                      A null return can also indicate that the map previously associated null with the key.
     */
    public function replace( $key, $value ) {
        $exists        = array_key_exists( $key, $this->map );
        $previousValue = $this->get( $key );
        if ( $exists ) {
            $this->map[ $key ] = $value;
        }

        return $previousValue;
    }

    /**
     * Replaces the entry for the specified key only if currently mapped to the specified value.
     *
     * @param $key
     * @param $newValue
     * @param $oldValue
     *
     * @return boolean true if the value was replaced
     */
    public function replaceIfEquals( $key, $newValue, $oldValue ) {
        $exists        = array_key_exists( $key, $this->map );
        $previousValue = $this->get( $key );
        if ( $exists && $previousValue === $oldValue ) {
            $this->map[ $key ] = $newValue;

            return true;
        }

        return false;
    }

    /**
     * Replaces each entry's value with the result of invoking the given function on that entry until all entries have been processed,
     * or the function throws an exception.
     * Exceptions thrown by the function are relayed to the caller.
     *
     * @param callable $callable
     *
     * @return void
     */
    public function replaceAll( callable $callable ) {
        foreach ( $this->map as $key => $value ) {
            $this->map[ $key ] = $callable( $key, $value );
        }
    }

    /**
     * @return int
     */
    public function size() {
        return sizeof( $this->map );
    }

    /**
     * @return Map
     */
    public function values() {
        return new static( array_values( $this->map ) );
    }

    /**
     * Attempts to compute a mapping for the specified key and its current mapped value (or null if there is no current mapping).
     * For example, to either create or append a String msg to a value mapping:
     * <code>
     *     $_empty = 'EMPTY';
     *     $map->compute( "foo", function( $k, $v ) use ( $_empty ) { ($v == null) ? $_empty : 'NO MORE ' . $_empty  } );
     * </code>
     *
     * If the function returns null, the mapping is removed (or remains absent if initially absent).
     * If the function itself throws an (unchecked) exception, the exception is rethrown, and the current mapping is left unchanged.
     *
     * @param          $key
     * @param callable $callable
     *
     * @return mixed|null the new value associated with the specified key, or null if none
     */
    public function compute( $key, callable $callable ) {
        $exists = array_key_exists( $key, $this->map );
        if ( $exists ) {
            $res = $callable( $key, $this->get( $key ) );
            if ( $res == null ) {
                unset( $this->map[ $key ] );
            } else {
                $this->map[ $key ] = $callable( $key, $this->get( $key ) );
            }
        }

        return null;
    }

    /**
     * If the specified key is not already associated with a value (or is mapped to null),
     * attempts to compute its value using the given mapping function and enters it into this map unless null.
     *
     * If the function returns null, no mapping is recorded. If the function itself throws an (unchecked) exception,
     * the exception is rethrown, and no mapping is recorded.
     *
     * The most common usage is to construct a new object serving as an initial mapped value or memoized result, as in:
     *
     * @param          $key
     * @param callable $callable
     *
     * @return mixed|null the current (existing or computed) value associated with the specified key, or null if the computed value is null
     */
    public function computeIfAbsent( $key, callable $callable ) {
        $exists        = array_key_exists( $key, $this->map );
        $previousValue = $this->get( $key );
        $res           = null;
        if ( !$exists || $previousValue !== null ) {
            $res = $callable( $key, $this->get( $key ) );
            if ( $res != null ) {
                $this->map[ $key ] = $res;
            }

        }

        return $res ?: null;

    }

    /**
     * If the value for the specified key is present and non-null, attempts to compute a new mapping given the key and its current mapped value.
     *
     * If the function returns null, the mapping is removed.
     * If the function itself throws an (unchecked) exception, the exception is rethrown, and the current mapping is left unchanged.
     *
     * @param          $key
     * @param callable $callable
     *
     * @return mixed|null the new value associated with the specified key, or null if none
     */
    public function computeIfPresent( $key, callable $callable ) {
        $exists        = array_key_exists( $key, $this->map );
        $previousValue = $this->get( $key );
        $res           = null;
        if ( $exists && $previousValue !== null ) {
            $res = $callable( $key, $this->map[ $key ] );
            if ( $res == null ) {
                unset( $this->map[ $key ] );
            } else {
                $this->map[ $key ] = $res;
            }
        }

        return $res ?: null;
    }

}