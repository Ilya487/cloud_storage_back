<?php

namespace App\Storage;

use App\Storage\BaseStorage;
use InvalidArgumentException;

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

    public function delete(int $id): bool
    {
        $filePath = $this->findPathById($id);
        if (is_null($filePath)) return false;

        return unlink($filePath);
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

    public function isFileExist(int $id): bool
    {
        $path = $this->findPathById($id);
        return is_file($path);
    }

    private function findPathById(int $id): string|false
    {
        $relativePath = array_find(scandir($this->storagePath . '/storage'), function ($path) use ($id) {
            if (is_dir($path)) return false;
            $fileName = pathinfo($path)['filename'];
            if ($fileName == $id) return true;
            else return false;
        });

        if (is_null($relativePath)) return false;
        else return $this->getFullPath(partPath: $relativePath);
    }

    private function getFullPath(?int $id = null, ?string $ext = null, ?string $partPath = null): string
    {
        if (!is_null($partPath)) return $this->storagePath . "/storage/$partPath";
        elseif (isset($id, $ext)) return $this->storagePath . "/storage/$id.$ext";
        else throw new InvalidArgumentException();
    }
}
