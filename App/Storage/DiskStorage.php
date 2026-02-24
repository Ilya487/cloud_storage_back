<?php

namespace App\Storage;

use App\Storage\BaseStorage;

class DiskStorage extends BaseStorage
{
    public function __construct(string $storagePath)
    {
        parent::__construct($storagePath);
        $path = $storagePath .  '/storage';

        if (!is_dir($path)) {
            mkdir($path);
        }
        $this->storagePath = $path;
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

    public function createFile(int $id, string $ext): string|false
    {
        $filePath = $this->storagePath . "/$id.$ext";
        if (file_exists($filePath)) return false;
        $handle = fopen($filePath, 'w');
        fclose($handle);

        return $filePath;
    }

    public function getPath(int $userId, string $partPath = '/'): string|false
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
