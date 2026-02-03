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
 * @extends ArrayObject<int, mixed>
 *
 */
class ArrayList extends ArrayObject implements ListInterface
{
    /**
     * @param array<int,mixed> $list
     */
    public function __construct(array $list = [])
    {
        if (!empty($list) && !array_is_list($list)) {
            throw new DomainException("Invalid list provided");
        }
        parent::__construct($list);
    }

    /**
     * @param $key
     *
     * @return mixed|null
     */
    public function offsetGet($key): mixed
    {
        if ($this->offsetExists($key)) {
            return parent::offsetGet($key);
        }

        return null;
    }

    /**
     * @param array<int,mixed> $list
     * @return ArrayList
     */
    public static function instance(array $list = []): ArrayList
    {
        return new static($list);
    }

    /**
     * Returns the element at the specified position in this list.
     *
     * @param int $key
     *
     * @return false|mixed|null the element at the specified position in this list
     */
    public function get(int $key): mixed
    {
        return $this->offsetGet($key);
    }

    /**
     * Appends the specified element to the end of this list.
     *
     * @param mixed $value
     *
     * @return true
     */
    public function add(mixed $value): bool
    {
        parent::append($value);
        return true;
    }

}
