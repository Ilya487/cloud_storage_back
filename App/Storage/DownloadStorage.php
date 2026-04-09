<?php

namespace App\Storage;

use App\Storage\BaseStorage;
use App\Tools\ArchiveBuilder;
use App\Tools\ArchiveException;

class DownloadStorage extends BaseStorage
{
    public function __construct(string $storagePath)
    {
        parent::__construct($storagePath);
        $archivesPath = $storagePath .  '/archives';

        if (!is_dir($archivesPath)) {
            mkdir($archivesPath);
        }
        $this->storagePath = $archivesPath;
    }

    public function createArchive(int $downloadId, string $prefix = ''): ArchiveBuilder|false
    {
        $path = $this->getPath($downloadId);
        if (mkdir($path) === false) return false;
        $name = $this->generateName($prefix);
        $path = "$path/$name";

        try {
            $zip = new ArchiveBuilder($path);
            return $zip;
        } catch (ArchiveException) {
            return false;
        }
    }

    public function getPathById(int $downloadId): string|false
    {
        $dirPath = $this->getPath($downloadId);
        $filePath = scandir($dirPath)[2];
        if (is_null($filePath)) return false;
        else return "$dirPath/$filePath";
    }

    public function deleteById(int $id): bool
    {
        $path = $this->getPath($id);
        if (!is_dir($path)) return true;

        return $this->deleteDirectoryRecursively($path);
    }

    private function getPath(int $id): string
    {
        return  $this->storagePath . "/$id";
    }

    private function generateName(string $prefix): string
    {
        $prefix = $prefix !== '' ? $prefix . '_' : '';
        return  $prefix . date('Ymd\THis') . '_' . uniqid();
    }
}
