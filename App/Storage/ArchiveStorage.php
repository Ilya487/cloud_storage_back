<?php

namespace App\Storage;

use App\Storage\BaseStorage;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ArchiveStorage extends BaseStorage
{
    public function __construct(string $storagePath)
    {
        parent::__construct($storagePath);

        if (!is_dir("$storagePath/archives")) {
            mkdir("$storagePath/archives");
        }
    }

    public function createArchive(int $userId, int $dirId, string $path): string|false
    {
        if (!is_dir($path)) return false;

        $zip = $this->getZip($userId, $dirId, basename($path));
        if ($zip === false) return false;

        $iterator = $this->getIterator($path);

        foreach ($iterator as $file) {
            $relativePath = mb_substr($file->getPathname(), mb_strlen($path) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else if ($file->isFile()) {
                if (!$zip->addFile($file->getPathname(), $relativePath)) return false;
                $zip->setCompressionName($relativePath, ZipArchive::CM_STORE);
            }
        }

        $archivePath = $zip->filename;
        $zip->close();
        return $archivePath;
    }

    private function getIterator(string $path): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    }

    private function getZip(int $userId, int $dirId, string $dirName): ZipArchive|false
    {
        $zip = new ZipArchive();
        $archiveName = uniqid($dirName . '_' . $userId . $dirId);
        $path = $this->storagePath . '/archives/' . $archiveName;
        $res = $zip->open($path . '.zip', ZipArchive::CREATE);
        if ($res === true) return $zip;
        else return false;
    }
}
