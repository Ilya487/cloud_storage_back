<?php

namespace App\Workers;

use App\Queue\Handlers\CreateArchiveJobHandler;
use App\Queue\Jobs\CreateArchiveJob;
use App\Tools\RedisConntect;

class CreateArchiveWorker extends Worker
{
    public function __construct(
        RedisConntect $redisFactory,
        private CreateArchiveJobHandler $handler
    ) {
        parent::__construct($redisFactory);
    }

    protected function getJobKey(): string
    {
        return CreateArchiveJob::JOB_KEY;
    }

    protected function handle(string $payload)
    {
        $arr = json_decode($payload, true);
        $userId = $arr['userId'];
        $taskId = $arr['taskId'];

        $this->handler->handle($userId, $taskId);
    }
}
