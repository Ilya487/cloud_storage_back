<?php

namespace App\Storage;

use Exception;

class DiskStorage
{
    public function __construct(private string $storagePath)
    {
        if (!is_dir($storagePath)) throw new Exception('Некорретный путь к хранилищу!');
    }

    public function initializeUserFolder(string $userId): bool
    {
        $res = mkdir($this->getFullPath($userId));
        return $res;
    }

    public function createDir(string $userId, string $dirName, string $path = '/'): bool
    {
        $path = $this->normalizePath($path);
        $path = $userId . $path . $dirName;

        return mkdir($this->getFullPath($path));
    }

    private function getFullPath(string $path): string
    {
        $path = $this->normalizePath($path);
        return $this->storagePath . $path;
    }

    private function normalizePath(string $path): string
    {
        if (strlen($path) == 0) return '/';
        if ($path[0] !== '/') $path = '/' . $path;
        if ($path[strlen($path) - 1] !== '/') $path = $path . '/';
        return $path;
    }
}
