<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Exceptions\NotFoundException;
use App\Models\FsObjectType;
use App\Repositories\FileSystemRepository;
use App\Storage\DiskStorage;

class DownloadService
{
    private const SERVER_FILES_PATH = '/files';

    public function __construct(
        private DiskStorage $diskStorage,
        private FileSystemRepository $fsRepo,
        private FilesDownloadPreparer $filePreparer
    ) {}

    public function getPathForFileDownload(int $userId, int $fileId)
    {
        $file = $this->fsRepo->getById($userId, $fileId);
        if ($file === false) throw new NotFoundException('Файл не найден');
        if ($file->type == FsObjectType::DIR) return OperationResult::createError(['message' => 'Попытка скачать папку']);

        $fullPath = $this->diskStorage->getPath($userId, $file->getPath());
        if ($fullPath === false) return OperationResult::createError(['message' => 'Не удалось скачать файл']);

        $pathForServer = $this->getPathForServer($fullPath);
        return OperationResult::createSuccess(['path' => $pathForServer]);
    }

    private function getPathForServer(string $path): string
    {
        $storagePath = $this->diskStorage->getStoragePath();
        return str_replace($storagePath, self::SERVER_FILES_PATH, $path);
    }
}
