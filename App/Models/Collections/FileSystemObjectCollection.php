<?php

namespace App\Models\Collections;

use App\Models\FileSystemObject;
use ArrayIterator;
use IteratorAggregate;

class FileSystemObjectCollection implements IteratorAggregate
{
    /**
     * @var FileSystemObject[]
     */
    private array $collection = [];
    private array $uniqueIds = [];

    private function __construct(array $collection = [])
    {
        $this->collection = $collection;
    }

    public static function createFromDbArr(array $fsObjectsArr): self
    {
        $collection = new self;
        foreach ($fsObjectsArr as $fsObjectArr) {
            $obj = FileSystemObject::createFromArr($fsObjectArr);
            $collection->add($obj);
        }

        return $collection;
    }

    public function in(int $id): bool
    {
        return in_array($id, $this->uniqueIds);
    }

    public function add(FileSystemObject $obj): self
    {
        $this->insertUniqueId($obj);
        $this->collection[] = $obj;
        return $this;
    }

    public function mergeCollections(FileSystemObjectCollection $collection, bool $preventDuplicates = false): self
    {
        foreach ($collection as $item) {
            if ($preventDuplicates && $this->in($item->id)) continue;
            $this->add($item);
        }

        return $this;
    }

    /**
     * @return ArrayIterator<int, FileSystemObject>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->collection);
    }

    public function len(): int
    {
        return count($this->collection);
    }

    private function insertUniqueId(FileSystemObject $fsObject)
    {
        if (!in_array($fsObject->id, $this->uniqueIds)) $this->uniqueIds[] = $fsObject->id;
    }
}
