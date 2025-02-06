<?php

namespace App\Storage;

use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
        $updatedFullPath = str_replace(basename($oldFullPath), $newName, $oldFullPath);

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

    private function getFullPath(string $partPath): string
    {
        $partPath = "/$userId" . $this->normalizePath($partPath, false);
        $fullPath =  $this->storagePath . $partPath;

        return $fullPath;
    }

    private function normalizePath(string $path, bool $processLastSlash = true): string
    {
        if (strlen($path) == 0) return '/';

        $path = str_replace('\\', '/', $path);
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        if ($processLastSlash && $path[-1] !== '/') {
            $path = $path . '/';
        }
        return $path;
    }
}
