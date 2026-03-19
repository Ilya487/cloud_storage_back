<?php

namespace App\Workers;

use App\Queue\Handlers\BuildFileJobHandler;
use App\Queue\Jobs\BuildFileJob;
use App\Tools\RedisConntect;
use App\Workers\Worker;

class FileBuildWorker extends Worker
{
    public function __construct(
        RedisConntect $redisFactory,
        private BuildFileJobHandler $handler
    ) {
        parent::__construct($redisFactory);
    }

    protected function getJobKey(): string
    {
        return BuildFileJob::JOB_KEY;
    }

    protected function handle(string $payload)
    {
        $arr = json_decode($payload, true);
        $userId = $arr['userId'];
        $sessionId = $arr['sessionId'];

        $this->handler->handle($userId, $sessionId);
    }
}
