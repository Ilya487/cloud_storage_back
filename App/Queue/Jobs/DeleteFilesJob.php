<?php

namespace App\Queue\Jobs;

use App\Queue\Jobs\Job;

class DeleteFilesJob implements Job
{
    public const JOB_KEY = 'delete_files';

    private function __construct(private string $key, private string $payload)
    {
        $this->key = $key;
        $this->payload = $payload;
    }

    public static function create(array $filesIds)
    {
        return new self(self::JOB_KEY, json_encode(['files' => $filesIds]));
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
