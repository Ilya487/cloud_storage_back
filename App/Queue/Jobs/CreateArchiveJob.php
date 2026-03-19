<?php

namespace App\Queue\Jobs;

use App\Queue\Jobs\Job;

class CreateArchiveJob implements Job
{
    public const JOB_KEY = 'archive_create';

    private function __construct(private string $key, private string $payload)
    {
        $this->key = $key;
        $this->payload = $payload;
    }

    public static function create(int $userId, int $taskId)
    {
        return new self(self::JOB_KEY, json_encode(['userId' => $userId, 'taskId' => $taskId]));
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getPayload(): string
    {
        return $this->payload;
    }
}
