<?php

namespace App\Storage;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use App\Storage\BaseStorage;

class DiskStorage extends BaseStorage
{
    public function initializeUserFolder(int $userId): bool
    {
        $res = mkdir($this->getFullPath($userId, ''));
        return $res;
    }

    public function createDir(int $userId, string $dirName, string $path = '/'): bool
    {
        $path = $this->normalizePath($path);
        $path = $path . $dirName;

        return mkdir($this->getFullPath($userId, $path));
    }

    public function renameDir(int $userId, string $newName, string $path): bool
    {
        $oldFullPath = $this->getFullPath($userId, $path);
        $updatedFullPath = dirname($oldFullPath) . "/$newName";

        return rename($oldFullPath, $updatedFullPath);
    }

    public function deleteDir(int $userId, string $path): bool
    {
        $fullPath = $this->getFullPath($userId, $path);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fullPath, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path => $obj) {
            if ($obj->isFile()) unlink($path);
            if ($obj->isDir()) rmdir($path);
        }

        return rmdir($fullPath);
    }

    public function moveItem(int $userId, string $currentPath, string $pathToMove)
    {
        $currentPath = $this->getFullPath($userId, $currentPath);
        $pathToMove = $this->getFullPath($userId, $pathToMove);

        $updatedPath = "$pathToMove/" . basename($currentPath);

        return rename($currentPath, $updatedPath);
    }
}
