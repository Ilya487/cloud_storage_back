<?php

namespace App\Models;

use App\Models\UploadSessionStatus;
use DateTime;
use Exception;

class UploadSession
{
    public function __construct(
        public readonly int $id,
        public readonly string $fileName,
        public readonly int $userId,
        public readonly ?string $destinationDirPath,
        public readonly int $flieSize,
        public readonly UploadSessionStatus $status,
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
            $data['destination_dir_path'],
            $data['file_size'],
            UploadSessionStatus::from($data['status']),
            new DateTime($data['last_updated_at']),
            $data['completed_chunks'],
            $data['total_chunks']
        );
    }

    public function setChunks($count)
    {
        if ($count < 1 || $count > $this->totalChunksCount) throw new Exception('Попытка установки невалидного значения');
        $this->completedChunksCount = $count;
    }

    public function getProgress(): float
    {
        return round($this->completedChunksCount / $this->totalChunksCount * 100, 2);
    }

    public function isUploadComplete(): bool
    {
        return $this->completedChunksCount == $this->totalChunksCount;
    }

    public function canBeBuilded(): bool
    {
        if ($this->isUploadComplete() && $this->status == UploadSessionStatus::UPLOADING)  return true;
        else return false;
    }
}
