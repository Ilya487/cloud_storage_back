<?php

namespace App\Workers;

use App\Tools\Logger;
use App\Tools\RedisConntect;

abstract class Worker
{
    public function __construct(protected RedisConntect $redisFactory) {}

    public function listen()
    {
        $redis = $this->redisFactory->getConnection();
        $jobKey = $this->getJobKey();

        while (true) {
            $result = $redis->brPop($jobKey, 30);
            if ($result) {
                try {
                    $payload = $result[1];
                    $this->handle($payload);
                } catch (\Throwable $e) {
                    Logger::writeLogFromError($e);
                    $redis->lPush($jobKey, $payload);
                    throw $e;
                }
            }
        }
    }

    abstract protected function getJobKey(): string;
    abstract protected function handle(string $payload);
}
