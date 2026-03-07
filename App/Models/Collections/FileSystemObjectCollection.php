<?php

namespace App\Models\Collections;

use App\Models\FileSystemObject;

/**
 * @extends Collection<FileSystemObject>
 */
class FileSystemObjectCollection extends Collection
{
    /**
     * @var FileSystemObject[]
     */
    private array $uniqueIds = [];

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

    public function getFolderContent(string $pathIds): FileSystemObjectCollection
    {
        $res = [];
        foreach ($this->collection as $file) {
            if (str_starts_with($file->getPathIds(), "$pathIds/"))
                $res[] = $file;
        }

        return new FileSystemObjectCollection($res);
    }

    /**
     * @param callable(FileSystemObject $fsObject): bool $cb
     */
    public function filter(callable $cb): self
    {
        $filteredArr = array_filter($this->collection, fn($value) => $cb($value));
        return new self($filteredArr);
    }

    private function insertUniqueId(FileSystemObject $fsObject)
    {
        if (!in_array($fsObject->id, $this->uniqueIds)) $this->uniqueIds[] = $fsObject->id;
    }
}
