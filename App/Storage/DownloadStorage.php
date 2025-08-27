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

        if (!is_dir("$storagePath/archives")) {
            mkdir("$storagePath/archives");
        }
    }

    public function createArchive(int $userId, string $prefix = ''): ArchiveBuilder|false
    {
        $path = $this->generateName($userId, $prefix);
        try {
            $zip = new ArchiveBuilder($path);
            return $zip;
        } catch (ArchiveException) {
            return false;
        }
    }

    private function generateName(int $userId, string $prefix): string
    {
        $prefix = $prefix !== '' ? $prefix . '_' : '';
        $archiveName = $prefix . date('Ymd\THis') . '_' . uniqid() . '_' . $userId;
        $generatedPath = $this->storagePath . '/archives/' . $archiveName;

        return $generatedPath;
    }
}
