<?php

namespace App\Repositories;

use App\Repositories\BaseRepository;

class UploadSessionRepository  extends BaseRepository
{
    public function createUploadSession(int $userId, string $fileName, string $fileType, int $totalChunks, ?int $destinationDirId)
    {
        $query = $this->queryBuilder->insert(['user_id', 'filename', 'file_type', 'destination_dir_id', 'total_chunks'])->build();
        return $this->insert($query, [
            'user_id' => $userId,
            'filename' => $fileName,
            'file_type' => $fileType,
            'destination_dir_id' => $destinationDirId,
            'total_chunks' => $totalChunks
        ]);
    }

    public function deleteSession(int $userId, int $sessionId)
    {
        $query = $this->queryBuilder->delete()->where('user_id', '=')->and('id', '=')->build();
        $this->delete($query, ['user_id' => $userId, 'id' => $sessionId]);
    }
}
