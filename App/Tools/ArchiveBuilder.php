<?php

namespace App\Tools;

use Exception;
use FilesystemIterator;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class ArchiveBuilder
{
    private ZipArchive $zip;
    private bool $isBuild = false;

    public function __construct(string $path)
    {
        $zip = new ZipArchive();

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== '.zip') {
            $path .= '.zip';
        }

        $res = $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($res === true) $this->zip = $zip;
        else throw new ArchiveException('Не удалось создать архив');
    }

    public function add(string $path): bool
    {
        if ($this->isBuild) throw new LogicException('Невозможно добавить файл: архив уже закрыт');

        $path = realpath($path);
        if ($path === false) return false;

        if (is_file($path)) {
            return $this->zip->addFile(basename($path));
        } elseif (is_dir($path)) {
            return $this->addFolder($path);
        }
        return false;
    }

    public function build(): string
    {
        $buildRes = $this->zip->close();
        if ($buildRes === false) throw new ArchiveException('Не удалось закрыть архив');
        $this->isBuild = true;
        return $this->zip->filename;
    }

    private function addFolder(string $path): bool
    {
        $iterator = $this->getDirIterator($path);
        $dirName = basename($path);
        $this->zip->addEmptyDir($dirName);

        foreach ($iterator as $file) {
            $relativePath = "$dirName/" . mb_substr($file->getPathname(), mb_strlen($path) + 1);

            if ($file->isDir()) {
                $res = $this->zip->addEmptyDir($relativePath);
                if ($res === false) return false;
            } else if ($file->isFile()) {
                if (!$this->zip->addFile($file->getPathname(), $relativePath)) return false;
                $this->zip->setCompressionName($relativePath, ZipArchive::CM_STORE);
            }
        }
        return true;
    }

    private function getDirIterator(string $path): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    }
}

class ArchiveException extends Exception {}
