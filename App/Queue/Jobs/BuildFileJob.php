<?php

namespace App\Queue\Jobs;

use App\Queue\Jobs\Job;

class BuildFileJob implements Job
{
    public const JOB_KEY = 'build_file';

    private function __construct(private string $key, private string $payload)
    {
        $this->key = $key;
        $this->payload = $payload;
    }

    public static function create(int $userId, int $sessionId)
    {
        return new self(self::JOB_KEY, json_encode(['userId' => $userId, 'sessionId' => $sessionId]));
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
