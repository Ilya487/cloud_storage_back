<?php

namespace App\UseCases;

use App\DTO\OperationResult;
use App\Exceptions\NotFoundException;
use App\Models\FsObjectType;
use App\Repositories\FileSystemRepository;
use App\Storage\DiskStorage;
use App\Storage\DownloadStorage;

class DownloadUseCase
{
    public function __construct(
        private DownloadStorage $downloadStorage,
        private DiskStorage $diskStorage,
        private FileSystemRepository $fsRepo,
    ) {}

    public function execute(int $userId, array $items): OperationResult
    {
        if (count($items) == 1) return $this->getPathForDownloadSingleObject($userId, $items[0]);

        $archive = $this->downloadStorage->createArchive($userId);
        if ($archive === false) return OperationResult::createError(['message' => 'Не удалось загрузить файлы']);

        $errorsCount = 0;
        foreach ($items as $fileId) {
            $fsObject =  $this->fsRepo->getById($userId, $fileId);
            if ($fsObject === false) {
                $errorsCount++;
                continue;
            }
            $fullPath = $this->diskStorage->getPath($userId, $fsObject->getPath());
            if ($archive->add($fullPath) === false) $errorsCount++;
        }

        if (count($items) == $errorsCount) return OperationResult::createError(['message' => 'Не удалось загрузить файлы']);
        return OperationResult::createSuccess(['path' => $archive->build(), 'type' => 'archive']);
    }

    private function getPathForDownloadSingleObject(int $userId, int $objectId): OperationResult
    {
        $fsObject =  $this->fsRepo->getById($userId, $objectId);
        if ($fsObject === false) throw new NotFoundException('Запрашиваемый файл не найден');

        if ($fsObject->type == FsObjectType::FILE) {
            $fullPath = $this->diskStorage->getPath($userId, $fsObject->getPath());
            if ($fullPath !== false) return OperationResult::createSuccess(['path' => $fullPath, 'type' => 'file']);
        }

        $archive = $this->downloadStorage->createArchive($userId);
        if ($archive === false) return OperationResult::createError(['message' => 'Не удалось загрузить файлы']);

        $fullPath = $this->diskStorage->getPath($userId, $fsObject->getPath());
        if ($archive->add($fullPath) === false) return OperationResult::createError(['message' => 'Не удалось загрузить файлы']);
        return OperationResult::createSuccess(['path' => $archive->build(), 'type' => 'archive']);
    }
}
