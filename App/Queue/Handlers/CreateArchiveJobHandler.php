<?php

namespace App\Queue\Handlers;

use App\Models\CreateArchiveTask;
use App\Models\CreateArchiveTaskStatus;
use App\Repositories\CreateArchiveTaskRepository;
use App\Repositories\FileSystemRepository;
use App\Repositories\UserRepository;
use App\Services\DownloadArchiveService;
use Exception;

class CreateArchiveJobHandler
{
    public function __construct(
        private CreateArchiveTaskRepository $taskRepo,
        private FileSystemRepository $fsRepo,
        private DownloadArchiveService $archiveCreator,
        private UserRepository $userRepo
    ) {}

    public function handle(int $userId, int $taskId)
    {
        $task = $this->taskRepo->getTaskById($userId, $taskId);
        if ($task === false) throw new Exception('Задача не найдена');

        $files = $this->fsRepo->getFileTreeByIds($task->userId, $task->filesId);
        if ($files === false) $this->handleError($task, 'Запрашиваемые файлы не найдены');

        $files = $files->filter(fn($fsObject) => !$fsObject->inTrash);
        if ($files->len() == 0) $this->handleError($task, 'Файлы находятся в корзине');

        if (count($task->filesId) == 1) {
            $prefix = $files->getById($task->filesId[0])->getName();
        }

        $creationRes = $this->archiveCreator->buildArchiveForDownload($task->id, $files, $prefix ?? '');
        if (!$creationRes->success) $this->handleError($task, 'Не удалось создать архив');

        $this->taskRepo->setStatus($task->userId, $task->id, CreateArchiveTaskStatus::READY);
        $this->userRepo->decrementDownloadSessionCount($task->userId);
    }

    private function handleError(CreateArchiveTask $task, string $msg)
    {
        $this->taskRepo->setStatus($task->userId, $task->id, CreateArchiveTaskStatus::ERROR);
        $this->userRepo->decrementDownloadSessionCount($task->userId);
        throw new Exception($msg);
    }
}
