<?php

namespace App\Workers;

use App\Queue\Handlers\DeleteFilesJobHandler;
use App\Queue\Jobs\DeleteFilesJob;
use App\Tools\RedisConntect;
use App\Workers\Worker;

require_once 'autoloader.php';

class DeleteFilesWorker extends Worker
{
    public function __construct(
        RedisConntect $redisFactory,
        private DeleteFilesJobHandler $handler
    ) {
        parent::__construct($redisFactory);
    }

    protected function getJobKey(): string
    {
        return DeleteFilesJob::JOB_KEY;
    }

    protected function handle(string $payload)
    {
        $arr = json_decode($payload, true);
        $filesIds = $arr['files'];
        if (empty($filesIds)) return;

        $this->handler->handle($filesIds);
    }
}
