<?php

namespace App\Queue;

use App\Queue\Jobs\Job;
use App\Tools\RedisConntect;

class Queue
{
    public function __construct(private RedisConntect $redisFactory) {}

    public  function push(Job $job)
    {
        $redis = $this->redisFactory->getConnection();
        $redis->lPush($job->getKey(), $job->getPayload());
    }
}
