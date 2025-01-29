<?php

namespace App\Storage;

use Exception;

class DiskStorage
{
    private string $storagePath;

    public function __construct(string $storagePath)
    {
        if (!is_dir($storagePath)) throw new Exception('Некорретный путь к хранилищу!');
        $storagePath = str_replace('\\', '/', $storagePath);

        $lastChar = $storagePath[mb_strlen($storagePath) - 1];
        if ($lastChar == '/') {
            $storagePath = substr($storagePath, 0, mb_strlen($storagePath) - 1);
        }

        $this->storagePath = $storagePath;
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
        $partPath = $this->normalizePath($partPath);
        return $this->storagePath . $partPath;
    }

    private function normalizePath(string $path): string
    {
        if (strlen($path) == 0) return '/';

        $path = str_replace('\\', '/', $path);
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        if ($path[mb_strlen($path) - 1] !== '/') {
            $path = $path . '/';
        }
        return $path;
    }
}
