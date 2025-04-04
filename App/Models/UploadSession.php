<?php

namespace App\Models;

use DateTime;

class UploadSession
{
    public function __construct(
        public readonly int $id,
        public readonly string $fileName,
        public readonly int $userId,
        public readonly ?int $destinationDirId,
        public readonly DateTime $lastUpdated,
        private int $completedChunksCount,
        public readonly int $totalChunksCount
    ) {}

    public static function createFromArr(array $data): UploadSession
    {
        return new self(
            $data['id'],
            $data['filename'],
            $data['user_id'],
            $data['destination_dir_id'],
            new DateTime($data['last_updated_at']),
            $data['completed_chunks'],
            $data['total_chunks']
        );
    }

    public function incrementCompletedChunks(): int|false
    {
        if ($this->isUploadComplete()) return false;

        $this->completedChunksCount++;
        return $this->completedChunksCount;
    }

    public function getProgress(): float
    {
        return round($this->completedChunksCount / $this->totalChunksCount * 100, 2);
    }

    public function isUploadComplete(): bool
    {
        return $this->completedChunksCount == $this->totalChunksCount;
    }
}
