<?php

namespace App\UseCases;

use App\DTO\OperationResult;
use App\Exceptions\NotFoundException;
use App\Models\FsObjectType;
use App\Repositories\FileSystemRepository;
use App\Services\FilesDownloadPreparer;
use App\Storage\DiskStorage;
use App\Storage\DownloadStorage;

class DownloadUseCase
{
    private const SERVER_FILES_PATH = '/files';

    public function __construct(
        private DownloadStorage $downloadStorage,
        private DiskStorage $diskStorage,
        private FileSystemRepository $fsRepo,
        private FilesDownloadPreparer $filePreparer
    ) {}

    public function execute(int $userId, array $items): OperationResult
    {
        $fsObjects = $this->fsRepo->getMany($userId, $items);
        if ($fsObjects === false) return OperationResult::createError(['message' => 'Не удалось загрузить файлы']);

        $prepareResult = $this->filePreparer->prepareFiles($userId, $fsObjects);
        if ($prepareResult->success) {
            $pathForServer = $this->getPathForServer($prepareResult->resPath);
            return OperationResult::createSuccess(['path' => $pathForServer]);
        } else return OperationResult::createError(['message' => 'Не удалось загрузить файлы']);
    }

    private function getPathForServer(string $path): string
    {
        $storagePath = $this->diskStorage->getStoragePath();
        return str_replace($storagePath, self::SERVER_FILES_PATH, $path);
    }
}
