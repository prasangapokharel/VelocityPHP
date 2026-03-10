<?php
/**
 * VelocityPHP Collection
 * Fluent array wrapper for working with data sets
 *
 * @package VelocityPHP
 * @version 1.1.0
 */

namespace App\Utils;

use ArrayAccess;
use Countable;
use Iterator;

class Collection implements ArrayAccess, Countable, Iterator
{
    /** @var array */
    protected $items;

    /** @var int */
    private $position = 0;

    /**
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = array_values($items);
    }

    /**
     * Create a new collection.
     *
     * @param array $items
     * @return static
     */
    public static function make(array $items = []): self
    {
        return new static($items);
    }

    /**
     * Filter items using a callback.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): self
    {
        return new static(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Transform each item using a callback.
     *
     * @param callable $callback
     * @return static
     */
    public function map(callable $callback): self
    {
        return new static(array_map($callback, $this->items));
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed    $initial
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Return only items where key matches value.
     *
     * @param string $key
     * @param mixed  $value
     * @return static
     */
    public function where(string $key, $value): self
    {
        return $this->filter(static function ($item) use ($key, $value) {
            return (is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null)) === $value;
        });
    }

    /**
     * Get the first item, optionally matching a callback.
     *
     * @param callable|null $callback
     * @param mixed         $default
     * @return mixed
     */
    public function first(?callable $callback = null, $default = null)
    {
        if ($callback === null) {
            return $this->items[0] ?? $default;
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Get the last item.
     *
     * @param callable|null $callback
     * @param mixed         $default
     * @return mixed
     */
    public function last(?callable $callback = null, $default = null)
    {
        if ($callback === null) {
            return !empty($this->items) ? end($this->items) : $default;
        }

        $result = $default;
        foreach ($this->items as $item) {
            if ($callback($item)) {
                $result = $item;
            }
        }

        return $result;
    }

    /**
     * Sort items by a callback or key name.
     *
     * @param callable|string|null $by
     * @return static
     */
    public function sortBy($by = null): self
    {
        $items = $this->items;

        if ($by === null) {
            sort($items);
        } elseif (is_string($by)) {
            $key = $by;
            usort($items, static function ($a, $b) use ($key) {
                $va = is_array($a) ? ($a[$key] ?? null) : ($a->$key ?? null);
                $vb = is_array($b) ? ($b[$key] ?? null) : ($b->$key ?? null);
                return $va <=> $vb;
            });
        } else {
            usort($items, $by);
        }

        return new static($items);
    }

    /**
     * Extract a single key from each item.
     *
     * @param string $key
     * @return static
     */
    public function pluck(string $key): self
    {
        return $this->map(static function ($item) use ($key) {
            return is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
        });
    }

    /**
     * Return only unique items.
     *
     * @param string|null $key
     * @return static
     */
    public function unique(?string $key = null): self
    {
        if ($key === null) {
            return new static(array_values(array_unique($this->items)));
        }

        $seen  = [];
        $items = [];

        foreach ($this->items as $item) {
            $val = is_array($item) ? ($item[$key] ?? null) : ($item->$key ?? null);
            if (!in_array($val, $seen, true)) {
                $seen[]  = $val;
                $items[] = $item;
            }
        }

        return new static($items);
    }

    /**
     * Split the collection into chunks of a given size.
     *
     * @param int $size
     * @return static
     */
    public function chunk(int $size): self
    {
        return new static(array_chunk($this->items, $size));
    }

    /**
     * Take only the first N items.
     *
     * @param int $limit
     * @return static
     */
    public function take(int $limit): self
    {
        return new static(array_slice($this->items, 0, $limit));
    }

    /**
     * Skip the first N items.
     *
     * @param int $offset
     * @return static
     */
    public function skip(int $offset): self
    {
        return new static(array_slice($this->items, $offset));
    }

    /**
     * Check if the collection contains no items.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if the collection contains items.
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Sum all items (or a specific key).
     *
     * @param string|null $key
     * @return int|float
     */
    public function sum(?string $key = null)
    {
        if ($key === null) {
            return array_sum($this->items);
        }

        return array_sum(array_map(
            static fn($item) => is_array($item) ? ($item[$key] ?? 0) : ($item->$key ?? 0),
            $this->items
        ));
    }

    /**
     * Get the average of all items (or a specific key).
     *
     * @param string|null $key
     * @return float|null
     */
    public function avg(?string $key = null): ?float
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->sum($key) / $this->count();
    }

    /**
     * Get the minimum value.
     *
     * @param string|null $key
     * @return mixed
     */
    public function min(?string $key = null)
    {
        $values = $key === null ? $this->items : $this->pluck($key)->toArray();
        return $values ? min($values) : null;
    }

    /**
     * Get the maximum value.
     *
     * @param string|null $key
     * @return mixed
     */
    public function max(?string $key = null)
    {
        $values = $key === null ? $this->items : $this->pluck($key)->toArray();
        return $values ? max($values) : null;
    }

    /**
     * Group items by a key.
     *
     * @param string $key
     * @return static
     */
    public function groupBy(string $key): self
    {
        $groups = [];

        foreach ($this->items as $item) {
            $groupKey = is_array($item) ? ($item[$key] ?? '') : ($item->$key ?? '');
            $groups[$groupKey][] = $item;
        }

        return new static($groups);
    }

    /**
     * Flatten a multi-dimensional collection one level deep.
     *
     * @return static
     */
    public function flatten(): self
    {
        $result = [];

        array_walk_recursive($this->items, static function ($item) use (&$result) {
            $result[] = $item;
        });

        return new static($result);
    }

    /**
     * Merge another array or Collection into this one.
     *
     * @param array|self $items
     * @return static
     */
    public function merge($items): self
    {
        $arr = $items instanceof self ? $items->toArray() : $items;
        return new static(array_merge($this->items, $arr));
    }

    /**
     * Check if a value exists in the collection.
     *
     * @param mixed $value
     * @return bool
     */
    public function contains($value): bool
    {
        return in_array($value, $this->items, true);
    }

    /**
     * Get all items as a plain array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Encode to JSON.
     *
     * @return string
     */
    public function toJson(): string
    {
        return (string) json_encode($this->items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    // --- ArrayAccess ---

    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool  { return isset($this->items[$offset]); }
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)            { return $this->items[$offset]; }
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void { $this->items[$offset] = $value; }
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void    { unset($this->items[$offset]); }

    // --- Countable ---

    public function count(): int { return count($this->items); }

    // --- Iterator ---

    #[\ReturnTypeWillChange]
    public function current()  { return $this->items[$this->position]; }
    public function key(): int { return $this->position; }
    public function next(): void  { $this->position++; }
    public function rewind(): void { $this->position = 0; }
    public function valid(): bool { return isset($this->items[$this->position]); }
}
