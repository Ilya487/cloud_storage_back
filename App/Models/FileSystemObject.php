<?php

namespace App\Models;

use Exception;

class FileSystemObject
{
    public function __construct(
        public readonly ?int $id,
        public readonly int $ownerId,
        private int|null $parentId,
        public readonly FsObjectType $type,
        private string $name,
        private string $path,
        private string $pathIds,
        public readonly int|null $size,
        public readonly bool $inTrash
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
            $arr['path_ids'],
            $arr['size'],
            $arr['deleted_at'] ? true : false,
        );
    }

    public static function createRootDir(int $ownerId): self
    {
        return new self(
            id: null,
            ownerId: $ownerId,
            parentId: null,
            type: FsObjectType::DIR,
            name: 'ROOT',
            path: '/',
            pathIds: '',
            size: null,
            inTrash: false
        );
    }

    public function rename(string $newName)
    {
        if (str_contains($newName, '/') || str_contains($newName, '\\')) throw new Exception('Имя не может содержать знаков: \\ /');
        $pathToDir = dirname($this->path);
        $updatedPath = $pathToDir == DIRECTORY_SEPARATOR ? '' . "/$newName" : $pathToDir . "/$newName";
        $this->name = $newName;
        $this->path = $updatedPath;

        return $updatedPath;
    }

    public function changeDir(FileSystemObject $toDir): string|false
    {
        if ($this->id == $toDir->id) {
            return false;
        }
        if ($this->parentId == $toDir->id) {
            return false;
        }
        if (str_starts_with($toDir->getPathIds(), $this->pathIds)) {
            return false;
        }

        $newDirPath = trim($toDir->getPath());
        if ($newDirPath[mb_strlen($newDirPath) - 1] == '/') {
            $updatedPath = "$newDirPath" . basename($this->path);
        } else $updatedPath = "$newDirPath/" . basename($this->path);

        $this->pathIds = $toDir->pathIds . '/' . $this->id;
        $this->path = $updatedPath;
        $this->parentId = $toDir->id;
        return $updatedPath;
    }

    public function getName(): string
    {
        return pathinfo($this->name, PATHINFO_FILENAME);
    }

    public function getBaseName(): string
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

    public function getExt(): string|false
    {
        if ($this->isFile()) {
            $ext = pathinfo($this->name, PATHINFO_EXTENSION);
            if (!$ext)  return false;
            return $ext;
        }
        return false;
    }

    public function getPathIds(): string
    {
        return $this->pathIds;
    }

    public function hasParent(): bool
    {
        if (is_null($this->parentId)) return false;
        else return true;
    }
}

enum FsObjectType: string
{
    case FILE = 'file';
    case DIR = 'folder';
}
