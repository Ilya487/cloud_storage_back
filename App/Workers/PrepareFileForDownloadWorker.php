<?php

namespace App\Workers;

require_once 'autoloader.php';

use App\Config\Container;
use App\Models\PrepareFilesTask;
use App\Models\PrepareFilesTaskStatus;
use App\Repositories\FileSystemRepository;
use App\Repositories\PreapareFilesTaskRepository;
use App\Services\FilesDownloadPreparer;
use App\Tools\ErrorHandler;
use Exception;

class PrepareFileForDownloadWorker
{
    public function __construct(
        private PreapareFilesTaskRepository $taskRepo,
        private FileSystemRepository $fsRepo,
        private FilesDownloadPreparer $filePreparer
    ) {}

    public function prepare(int $userId, int $taskId)
    {
        $task = $this->taskRepo->findById($userId, $taskId);
        if ($task === false) throw new Exception('Задача не найдена');

        $files = $this->fsRepo->getMany($task->userId, $task->filesId);
        if ($files === false) $this->handleError($task, 'Запрашиваемые файлы не найдены');

        $prepareRes = $this->filePreparer->prepareFiles($task->id, $files);
        if (!$prepareRes->success) $this->handleError($task, 'Не удалось создать архив');

        $this->taskRepo->setStatus($task->userId, $task->id, PrepareFilesTaskStatus::READY);
    }

    private function handleError(PrepareFilesTask $task, string $msg)
    {
        $this->taskRepo->setStatus($task->userId, $task->id, PrepareFilesTaskStatus::ERROR);
        throw new Exception($msg);
    }
}

ErrorHandler::handle(function () {
    $worker = Container::getInstance()->resolve(PrepareFileForDownloadWorker::class);
    ['t' => $taskId, 'u' => $userId] = getopt('t:u:');

    $worker->prepare($userId, $taskId);
});
