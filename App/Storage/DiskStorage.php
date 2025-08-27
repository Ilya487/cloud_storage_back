<?php

namespace App\Storage;

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

    public function renameObject(int $userId, string $newName, string $path): bool
    {
        $oldFullPath = $this->getFullPath($userId, $path);
        $updatedFullPath = dirname($oldFullPath) . "/$newName";
        if (is_file($updatedFullPath)) return false;

        return rename($oldFullPath, $updatedFullPath);
    }

    public function delete(int $userId, string $path): bool
    {
        $fullPath = $this->getFullPath($userId, $path);

        if (is_dir($fullPath)) {
            return $this->deleteDirectoryRecursively($fullPath);
        } else if (is_file($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function moveItem(int $userId, string $currentPath, string $pathToMove): bool
    {
        $currentPath = $this->getFullPath($userId, $currentPath);
        $pathToMove = $this->getFullPath($userId, $pathToMove);

        $updatedPath = "$pathToMove/" . basename($currentPath);
        if (is_file($updatedPath)) return false;

        return rename($currentPath, $updatedPath);
    }

    public function putContentInFile(int $userId, string $dirPath, string $filename, string $data = '', bool $clear = false): bool
    {
        $fullPath = $this->getFullPath($userId, $dirPath) . "/$filename";
        $res = file_put_contents($fullPath, $data, $clear == true ? 0 : FILE_APPEND);
        if ($res === false) return false;
        else return true;
    }

    public function getPath(int $userId, string $partPath): string|false
    {
        $fullPath = $this->getFullPath($userId, $partPath);
        if (is_file($fullPath) || is_dir($fullPath)) return $fullPath;
        else return false;
    }

    public function getFileSize(int $userId, string $path): int|false
    {
        $fullPath = $this->getFullPath($userId, $path);
        return filesize($fullPath);
    }

    private function getFullPath(int $userId, string $partPath): string
    {
        $partPath = "/$userId" . $this->normalizePath($partPath, false);
        $fullPath =  $this->storagePath . $partPath;

        return $fullPath;
    }
}
