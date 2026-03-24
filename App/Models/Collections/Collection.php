<?php

namespace App\Models\Collections;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;

/**
 * @template T
 * @implements IteratorAggregate<int, T>
 * @implements ArrayAccess<int, T>
 */
abstract class Collection implements IteratorAggregate, ArrayAccess
{
    /** @var T[] */
    protected array $collection = [];

    /**
     * @param T[] $collection
     */
    protected function __construct(array $collection = [])
    {
        $this->collection = $collection;
    }

    /**
     * @return ArrayIterator<int, T>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->collection);
    }

    public function len(): int
    {
        return count($this->collection);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->collection[$offset]);
    }

    /** @return T|null */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->collection[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void {}

    public function offsetUnset(mixed $offset): void {}

    /**
     * @param callable(T $object): bool $cb
     */
    public function filter(callable $cb): self
    {
        $filteredArr = array_filter($this->collection, fn($value) => $cb($value));
        return new self($filteredArr);
    }
}
