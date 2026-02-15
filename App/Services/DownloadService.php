<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Exceptions\NotFoundException;
use App\Repositories\FileSystemRepository;
use App\Repositories\PreapareFilesTaskRepository;
use App\Storage\DiskStorage;
use App\Storage\DownloadStorage;
use App\Workers\WorkerManager;

class DownloadService
{
    private const SERVER_FILES_PATH = '/files';
    private const MAX_FILES_COUNT = 5;
    private const MAX_SESSIONS_COUNT = 3;

    public function __construct(
        private DiskStorage $diskStorage,
        private FileSystemRepository $fsRepo,
        private PreapareFilesTaskRepository $prepareRepo,
        private DownloadStorage $downloadStorage
    ) {}

    public function getFileServerPath(int $userId, int $fileId)
    {
        $file = $this->fsRepo->getById($userId, $fileId);
        if ($file === false) throw new NotFoundException('Файл не найден');
        if (!$file->isFile()) return OperationResult::createError(['message' => 'Попытка получить путь папки']);

        $fullPath = $this->diskStorage->getPath($userId, $file->getPath());
        if ($fullPath === false) return OperationResult::createError(['message' => 'Не удалось получить путь файла']);

        $pathForServer = $this->getPathForServer($fullPath);
        return OperationResult::createSuccess(['path' => $pathForServer]);
    }

    public function iniArchive(int $userId, array $filesId): OperationResult
    {
        $files = $this->fsRepo->getMany($userId, $filesId);
        if ($files === false) throw new NotFoundException('Запрашиваемые файлы не найдены');
        if (count($files) == 1 && $files[0]->isFile()) return OperationResult::createError(['message' => 'Попытка скачать один файл']);
        if (count($files) > self::MAX_FILES_COUNT) return OperationResult::createError(['message' => 'Превышено допустимое число файлов']);

        $taskId = $this->prepareRepo->createTask($userId, $filesId, self::MAX_SESSIONS_COUNT);
        if ($taskId === false) return OperationResult::createError(['message' => 'Превышен лимит одновременных скачиваний']);
        WorkerManager::startPrepareFilesForDownloadWorker($userId, $taskId);

        return OperationResult::createSuccess(['taskId' => $taskId]);
    }

    public function checkArchiveStatus(int $userId, int $taskId): OperationResult
    {
        $task = $this->prepareRepo->getById($userId, $taskId);
        if ($task === false) throw new NotFoundException('Задача с данным айди не найдена');
        return OperationResult::createSuccess(['status' => $task->status->value]);
    }

    public function getPathForArchiveDownlaod(int $userId, int $taskId)
    {
        $task = $this->prepareRepo->getById($userId, $taskId);
        if ($task === false) throw new NotFoundException('Задача с данным айди не найдена');

        if ($task->hasError()) return OperationResult::createError(['message' => 'Произошла ошибка при создании архива']);
        if (!$task->isReady()) return OperationResult::createError(['message' => 'Архив еще не готов']);

        $fullPath = $this->downloadStorage->getPathById($task->id);
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
