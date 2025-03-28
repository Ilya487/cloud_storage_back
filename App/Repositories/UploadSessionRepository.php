<?php

namespace App\Repositories;

use App\Models\UploadSession;
use App\Repositories\BaseRepository;
use App\Tools\QueryBuilder;
use PDO;

class UploadSessionRepository  extends BaseRepository
{
    public function createUploadSession(int $userId, string $fileName, int $totalChunks, ?int $destinationDirId)
    {
        $query = $this->queryBuilder->insert(['user_id', 'filename', 'destination_dir_id', 'total_chunks'])->build();
        return $this->insert($query, [
            'user_id' => $userId,
            'filename' => $fileName,
            'destination_dir_id' => $destinationDirId,
            'total_chunks' => $totalChunks
        ]);
    }

    public function deleteSession(int $userId, int $sessionId)
    {
        $query = $this->queryBuilder->delete()->where('user_id', '=')->and('id', '=')->build();
        $this->delete($query, ['user_id' => $userId, 'id' => $sessionId]);
    }

    public function getById(int $userId, int $sessionId): UploadSession|false
    {
        $query = $this->queryBuilder->select()->where('user_id', '=')->and('id', '=')->build();
        $data = $this->fetchOne($query, ['user_id' => $userId, 'id' => $sessionId]);
        return $data == false ? false : UploadSession::createFromArr($data);
    }

    public function updateCompletedChunks(int $uploadSessionId, int $completedChunks)
    {
        $query = $this->queryBuilder->update(['completed_chunks'])->where('id', '=')->build();
        $this->update($query, ['completed_chunks' => $completedChunks, 'id' => $uploadSessionId]);
    }

    public function isNameExist(int $userId, string $fileName, ?int $destinationDirId)
    {
        $query = $this->queryBuilder->count()->where('user_id', '=')->and('filename', '=');
        if (is_null($destinationDirId)) {
            $query = $query->and('destination_dir_id', QueryBuilder::IS_NULL)->build();
            $params = ['user_id' => $userId, 'filename' => $fileName];
        } else {
            $query = $query->and('destination_dir_id', '=')->build();
            $params = ['user_id' => $userId, 'filename' => $fileName, 'destination_dir_id' => $destinationDirId];
        }

        return $this->fetchOne($query, $params, PDO::FETCH_NUM)[0] != 0;
    }

    public function getUserSessionsCount(int $userId): int
    {
        $query = $this->queryBuilder->count()->where('user_id', '=')->build();
        return $this->fetchOne($query, ['user_id' => $userId], PDO::FETCH_NUM)[0];
    }

    /**
     * @return UploadSession[]
     */
    public function getUserSessions(int $userId): array
    {
        $query = $this->queryBuilder->select()->where('user_id', '=')->build();
        $data = $this->fetchAll($query, ['user_id' => $userId]);
        $res = [];
        foreach ($data as $session) {
            $res[] = UploadSession::createFromArr($session);
        }
        return $res;
    }
}
