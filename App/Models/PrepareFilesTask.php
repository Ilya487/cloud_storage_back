<?php

namespace App\Models;

use App\Models\PrepareFilesTaskStatus;
use DateTime;

class PrepareFilesTask
{
    public function __construct(
        public readonly int $id,
        public readonly int $userId,
        public readonly array $filesId,
        public readonly PrepareFilesTaskStatus $status,
        public readonly DateTime $expiredAt
    ) {}

    public static function createFromArr(array $arr)
    {
        return new PrepareFilesTask(
            $arr['id'],
            $arr['user_id'],
            array_map('intval', explode(',', $arr['files_id'])),
            PrepareFilesTaskStatus::from($arr['status']),
            new DateTime($arr['expired_at'])
        );
    }

    public function isReady(): bool
    {
        return $this->status == PrepareFilesTaskStatus::READY;
    }

    public function hasError(): bool
    {
        return $this->status == PrepareFilesTaskStatus::ERROR;
    }

    public function isExpired(): bool
    {
        return time() > $this->expiredAt->getTimestamp();
    }
}
