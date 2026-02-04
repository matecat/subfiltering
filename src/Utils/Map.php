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
use Countable;
use DomainException;
use Iterator;
use IteratorAggregate;

/**
 * A Java like Interface helper for key/value php arrays, with safe access to the elements (no warning for undefined index)
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class Map implements ArrayAccess, IteratorAggregate, Countable, MapInterface
{

    /**
     * @var array<string,mixed>
     */
    private array $map;

    /**
     * @param array<int|string,mixed> $map
     */
    public function __construct(array $map = [])
    {
        if (!empty($map) && array_is_list($map)) {
            throw new DomainException("Invalid map provided");
        }
        /** @var array<string,mixed> $map */
        $this->map = $map;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return sizeof($this->map);
    }


    /**
     * @return ArrayIterator<string, mixed>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->map);
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->map);
    }

    /**
     * @param string $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param mixed $value
     *
     * @return void
     */
    public function offsetSet($offset, mixed $value): void
    {
        $this->put($offset, $value);
    }

    /**
     * @param $offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * Static helper for constructor
     *
     * @param array<string, mixed> $map
     *
     * @return static
     */
    public static function instance(array $map = []): Map
    {
        return new static($map);
    }

    /**
     * Safe key access. Avoid warnings.
     *
     * @param string $needle
     *
     * @return mixed|null
     */
    public function get(string $needle): mixed
    {
        return $this->getOrDefault($needle, null);
    }

    /**
     * @param string $needle
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOrDefault(string $needle, mixed $default): mixed
    {
        return array_key_exists($needle, $this->map) ? $this->map [$needle] : $default;
    }

    /**
     * Removes all the mappings from this map.
     * @return void
     */
    public function clear(): void
    {
        $this->map = [];
    }

    /**
     * @param string $offset
     * @return bool true if this map contains a mapping for the specified key.
     */
    public function containsKey(string $offset): bool
    {
        return array_key_exists($offset, $this->map);
    }

    /**
     * Returns true if this map maps one or more keys to the specified value.
     * If the value is found more than once, the first matching key is returned.
     *
     * @param mixed $value
     *
     * @return boolean
     */
    public function containsValue(mixed $value): bool
    {
        return !empty(array_search($value, $this->map, true));
    }

    /**
     * Iterates over each entry in the map and applies the given callable function.
     * The callable function receives the key and value of each entry as arguments.
     *
     * Actions are performed in the order of the map's entry set iteration.
     * If the callable throws an exception, the iteration is stopped, and the exception is propagated.
     *
     * @param callable $callable A function to execute for each entry in the map.
     *                           The function should accept two parameters: the key and the value.
     *
     * @return void
     */
    public function for_each(callable $callable): void
    {
        foreach ($this->map as $k => $v) {
            $callable($k, $v);
        }
    }

    /**
     * Returns true if this map contains no key-value mappings.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->map);
    }

    /**
     * @return string[]
     */
    public function keySet(): array
    {
        return array_keys($this->map);
    }

    /**
     * Associates the specified value with the specified key in this map. If the map previously contained a mapping for the key, the old value is replaced.
     *
     * @param string $offset
     * @param mixed $value
     *
     * @return mixed|null the previous value associated with `key`, or null if there was no mapping for `key`.
     */
    public function put(string $offset, mixed $value): mixed
    {
        $previousValue = $this->get($offset);
        $this->map[$offset] = $value;

        return $previousValue;
    }

    /**
     * @param Iterator<string, mixed>|array<string, mixed> $map
     *
     * @return void
     */
    public function putAll(Iterator|array $map): void
    {
        foreach ($map as $k => $v) {
            $this->map[$k] = $v;
        }
    }

    /**
     * If the specified key is not already associated with a value (or is mapped to null) associates it with the given value and returns null, else returns the current value.
     *
     * @param string $offset
     * @param mixed $value
     *
     * @return mixed|null the previous value associated with the specified key, or null if there was no mapping for the key.
     *               A null return can also indicate that the map previously associated null with the `key`
     */
    public function putIfAbsent(string $offset, mixed $value): mixed
    {
        $previousValue = $this->get($offset);
        if ($previousValue === null) {
            $this->map[$offset] = $value;
        }

        return $previousValue;
    }

    /**
     * Removes the mapping for the specified key from this map if present.
     *
     * @param string $offset
     *
     * @return bool true if the value was removed
     */
    public function remove(string $offset): bool
    {
        $exists = array_key_exists($offset, $this->map);
        if ($exists) {
            unset($this->map[$offset]);
        }

        return $exists;
    }

    /**
     * Replaces the entry for the specified key only if it is currently mapped to some value.
     *
     * @return mixed|null the previous value associated with the specified key, or null if there was no mapping for the key.
     *                      A null return can also indicate that the map previously associated null with the key.
     */
    public function replace(string $offset, mixed $value): mixed
    {
        $exists = array_key_exists($offset, $this->map);
        $previousValue = $this->get($offset);
        if ($exists) {
            $this->map[$offset] = $value;
        }

        return $previousValue;
    }

    /**
     * Replaces the entry for the specified key only if currently mapped to the specified value.
     *
     * @param string $offset
     * @param mixed $newValue
     * @param mixed $oldValue
     *
     * @return boolean true if the value was replaced
     */
    public function replaceIfEquals(string $offset, mixed $newValue, mixed $oldValue): bool
    {
        $exists = array_key_exists($offset, $this->map);
        $previousValue = $this->get($offset);
        if ($exists && $previousValue === $oldValue) {
            $this->map[$offset] = $newValue;

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
    public function replaceAll(callable $callable): void
    {
        foreach ($this->map as $offset => $value) {
            $this->map[$offset] = $callable($offset, $value);
        }
    }

    /**
     * @return int
     */
    public function size(): int
    {
        return sizeof($this->map);
    }

    /**
     * @return array<int, mixed>
     */
    public function values(): array
    {
        return array_values($this->map);
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
     * @param string $offset
     * @param callable $callable
     *
     * @return mixed|null the new value associated with the specified key, or null if none
     */
    public function compute(string $offset, callable $callable): mixed
    {
        $exists = array_key_exists($offset, $this->map);
        if ($exists) {
            $res = $callable($offset, $this->get($offset));
            if ($res == null) {
                unset($this->map[$offset]);
            } else {
                $this->map[$offset] = $callable($offset, $this->get($offset));

                return $this->map[$offset];
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
     * The most common usage is to construct a new object serving as an initially mapped value or memoized result, as in:
     *
     * @param string $offset
     * @param callable $callable
     *
     * @return mixed|null the current (existing or computed) value associated with the specified key, or null if the computed value is null
     */
    public function computeIfAbsent(string $offset, callable $callable): mixed
    {
        $exists = array_key_exists($offset, $this->map);
        $previousValue = $this->get($offset);
        $res = null;
        if (!$exists || $previousValue === null) {
            $res = $callable($offset, $this->get($offset));
            if ($res !== null) {
                $this->map[$offset] = $res;
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
     * @param string $offset
     * @param callable $callable
     *
     * @return mixed|null the new value associated with the specified key, or null if none
     */
    public function computeIfPresent(string $offset, callable $callable): mixed
    {
        $exists = array_key_exists($offset, $this->map);
        $previousValue = $this->get($offset);
        $res = null;
        if ($exists && $previousValue !== null) {
            $res = $callable($offset, $this->map[$offset]);
            if ($res == null) {
                unset($this->map[$offset]);
            } else {
                $this->map[$offset] = $res;
            }
        }

        return $res ?: null;
    }

}