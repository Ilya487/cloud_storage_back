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
        $filePath = $this->getFullPath($id, $ext);
        if (file_exists($filePath)) return false;
        $handle = fopen($filePath, 'w');
        fclose($handle);

        return $filePath;
    }

    public function getPath(int $id, string $ext): string|false
    {
        $fullPath = $this->getFullPath($id, $ext);
        if (is_file($fullPath)) return $fullPath;
        else return false;
    }

    public function getFileSize(int $userId, string $path): int|false
    {
        $fullPath = $this->getFullPath($userId, $path);
        return filesize($fullPath);
    }

    private function getFullPath(int $id, string $ext): string
    {
        return $this->storagePath . "/storage/$id.$ext";
    }
}
