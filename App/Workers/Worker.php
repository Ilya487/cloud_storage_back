<?php

namespace App\Workers;

use App\Tools\ErrorHandler;
use App\Tools\RedisConntect;

abstract class Worker
{
    public function __construct(protected RedisConntect $redisFactory) {}

    public function listen()
    {
        while (true) {
            $redis = $this->redisFactory->getConnection();
            $payload = $redis->brPop($this->getJobKey(), 10)[1];
            if ($payload) {
                ErrorHandler::handle(fn() => $this->handle($payload));
            }
        }
    }

    abstract protected function getJobKey(): string;
    abstract protected function handle(string $payload);
}
