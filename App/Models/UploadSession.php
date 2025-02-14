<?php

namespace App\Models;

class UploadSession
{
    public function __construct(
        public readonly int $id,
        public readonly string $fileName,
        public readonly int $userId,
        public readonly ?int $destinationDirId,
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
