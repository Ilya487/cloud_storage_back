<?php

namespace App\Scheduler;

use App\Repositories\CreateArchiveTaskRepository;
use App\Storage\DownloadStorage;

class DeleteExpiredArchives
{
    private const DELETE_LIMIT = 100;

    public function __construct(
        private DownloadStorage $downloadStorage,
        private CreateArchiveTaskRepository $taskRepo
    ) {}

    public function handle()
    {
        $expiredTasks = $this->taskRepo->getExpiredTasks(self::DELETE_LIMIT);
        foreach ($expiredTasks as $task) {
            $deleteRes = $this->downloadStorage->deleteById($task->id);
            if (!$deleteRes) continue;

            $this->taskRepo->deleteTaskById($task->id);
        }
    }
}
