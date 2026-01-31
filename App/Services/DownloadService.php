<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Exceptions\NotFoundException;
use App\Models\FsObjectType;
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
        private PreapareFilesTaskRepository $preapareRepo,
        private DownloadStorage $downloadStorage
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

    public function iniArchiveCreation(int $userId, array $filesId): OperationResult
    {
        $files = $this->fsRepo->getMany($userId, $filesId);
        if ($files == false) throw new NotFoundException('Запрашиваемые файлы не найдены');
        if (count($files) == 1 && $files[0]->type == FsObjectType::FILE) return OperationResult::createError(['message' => 'Попытка скачать один файл']);
        if (count($files) > self::MAX_FILES_COUNT) return OperationResult::createError(['message' => 'Превышено допустимое число файлов']);

        $sessionsCount = $this->preapareRepo->getUserTaskCount($userId);
        if ($sessionsCount > self::MAX_SESSIONS_COUNT) return OperationResult::createError(['message' => 'Превышен лимит одновременных загрузок']);

        $taskId = $this->preapareRepo->createTask($userId, $filesId);
        WorkerManager::startPrepareFilesForDownloadWorker($userId, $taskId);

        return OperationResult::createSuccess(['taskId' => $taskId]);
    }

    public function checkArchiveStatus(int $userId, int $taskId): OperationResult
    {
        $task = $this->preapareRepo->getById($userId, $taskId);
        if ($task === false) throw new NotFoundException('Задача с данным айди не найдена');
        return OperationResult::createSuccess(['status' => $task->status->value]);
    }

    public function getPathForArchiveDownlaod(int $userId, int $taskId)
    {
        $task = $this->preapareRepo->getById($userId, $taskId);
        if ($task === false) throw new NotFoundException('Задача с данным айди не найдена');

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
