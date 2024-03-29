<?php
declare(strict_types=1);

namespace Src\Support;

use ArrayIterator;

/**
 * Simple collection realization with only necessary for this project methods.
 *
 * Based on Laravel's Illuminate\Support\Collection class.
 *
 * @package Src\Support
 */
class Collection implements \IteratorAggregate
{
    /**
     * The items contained in the collection.
     *
     * @var array
     */
    private array $items;

    /**
     * Create a new collection.
     *
     * @param array $items
     * @return void
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        return \count($this->items);
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null  $callback
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new static(\array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
        }

        return new static(\array_filter($this->items));
    }

    /**
     * Get the first item from the collection.
     *
     * @return mixed|null
     */
    public function first()
    {
        $firstKey = array_key_first($this->items);

        if ($firstKey === null) {
            return null;
        }

        return $this->items[$firstKey];
    }

    /**
     * Get an iterator for the items.
     *
     * @return ArrayIterator
     */
    public function getIterator(): \Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * @param  string  $separator
     * @return string
     */
    public function implode(string $separator = Str::EMPTY): string
    {
        return \implode($separator, $this->items);
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys()
    {
        return new static(\array_keys($this->items));
    }

    /**
     * Put an item in the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return static
     */
    public function put($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }

        return $this;
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return static
     */
    public function values()
    {
        return new static(\array_values($this->items));
    }
}