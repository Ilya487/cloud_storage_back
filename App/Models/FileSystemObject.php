<?php

namespace App\Models;

class FileSystemObject
{
    public function __construct(
        public readonly int $id,
        public readonly int $ownerId,
        private int|null $parentId,
        public readonly FsObjectType $type,
        private string $name,
        private string $path,
        public readonly int|null $size
    ) {}

    public static function createFromArr(array $arr): FileSystemObject
    {
        return new self(
            $arr['id'],
            $arr['user_id'],
            $arr['parent_id'],
            FsObjectType::from($arr['type']),
            $arr['name'],
            $arr['path'],
            $arr['size'],
        );
    }

    public function rename(string $newName)
    {
        $pathToDir = dirname($this->path);
        $updatedPath = $pathToDir == DIRECTORY_SEPARATOR ? '' . "/$newName" : $pathToDir . "/$newName";
        $this->name = $newName;
        $this->path = $updatedPath;

        return $updatedPath;
    }

    public function changeDir(?int $toDirId, string $newDirPath): string|false
    {
        if ($this->id == $toDirId) {
            return false;
        }
        if ($this->parentId == $toDirId) {
            return false;
        }

        $newDirPath = trim($newDirPath);
        if ($newDirPath[mb_strlen($newDirPath) - 1] == '/') {
            $updatedPath = "$newDirPath" . basename($this->path);
        } else $updatedPath = "$newDirPath/" . basename($this->path);

        $this->path = $updatedPath;
        $this->parentId = $toDirId;
        return $updatedPath;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getParentId()
    {
        return $this->parentId;
    }

    public function isFile(): bool
    {
        return $this->type == FsObjectType::FILE;
    }
}

enum FsObjectType: string
{
    case FILE = 'file';
    case DIR = 'folder';
}
