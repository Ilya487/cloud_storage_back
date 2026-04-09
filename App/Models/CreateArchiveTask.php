<?php

namespace App\Models;

use App\Models\CreateArchiveTaskStatus;
use DateTime;

class CreateArchiveTask
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly array $filesId,
        public readonly CreateArchiveTaskStatus $status,
        public readonly DateTime $expiredAt
    ) {}

    public static function createFromArr(array $arr)
    {
        return new CreateArchiveTask(
            $arr['id'],
            $arr['user_id'],
            array_map('intval', explode(',', $arr['files_id'])),
            CreateArchiveTaskStatus::from($arr['status']),
            new DateTime($arr['expired_at'])
        );
    }

    public function isReady(): bool
    {
        return $this->status == CreateArchiveTaskStatus::READY;
    }

    public function hasError(): bool
    {
        return $this->status == CreateArchiveTaskStatus::ERROR;
    }

    public function isExpired(): bool
    {
        return time() > $this->expiredAt->getTimestamp();
    }
}
