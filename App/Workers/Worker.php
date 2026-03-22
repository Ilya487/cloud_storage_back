<?php

namespace App\Workers;

use App\Tools\Logger;
use App\Tools\RedisConntect;

abstract class Worker
{
    public function __construct(protected RedisConntect $redisFactory) {}

    public function listen()
    {
        while (true) {
            $redis = $this->redisFactory->getConnection();
            $jobKey = $this->getJobKey();

            $payload = $redis->brPop($jobKey, 10)[1];
            if ($payload) {
                try {
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
