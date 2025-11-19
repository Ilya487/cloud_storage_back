<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\UploadSession;
use App\Models\UploadSessionStatus;
use App\Repositories\BaseRepository;
use PDO;

class UploadSessionRepository  extends BaseRepository
{
    public function createUploadSession(int $userId, string $fileName, int $totalChunks, ?int $destinationDirId, int $fileSize)
    {
        $query = $this->queryBuilder->insert(['user_id', 'filename', 'destination_dir_id', 'total_chunks', 'file_size'])->build();
        return $this->insert($query, [
            'user_id' => $userId,
            'filename' => $fileName,
            'destination_dir_id' => $destinationDirId,
            'total_chunks' => $totalChunks,
            'file_size' => $fileSize
        ]);
    }

    public function deleteSession(int $userId, int $sessionId)
    {
        $query = $this->queryBuilder
            ->delete()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))
            ->build();
        $this->delete($query, ['user_id' => $userId, 'id' => $sessionId]);
    }

    public function getById(int $userId, int $sessionId): UploadSession|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))
            ->build();
        $data = $this->fetchOne($query, ['user_id' => $userId, 'id' => $sessionId]);
        return $data == false ? false : UploadSession::createFromArr($data);
    }

    public function incrementCompletedChunks(int $uploadSessionId): int|false
    {
        $updateQuery = $this->queryBuilder->incrementField('completed_chunks')->where(Expression::equal('id'))->build();
        $selectQuery = $this->queryBuilder->select(['completed_chunks'])->where(Expression::equal('id'))->build();

        $this->beginTransaction();
        $this->update($updateQuery, ['id' => $uploadSessionId]);
        $count = $this->fetchOne($selectQuery, ['id' => $uploadSessionId])['completed_chunks'];
        $this->submitTransaction();

        return $count;
    }

    public function isNameExist(int $userId, string $fileName, ?int $destinationDirId)
    {
        $query = $this->queryBuilder
            ->count()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('filename'));

        if (is_null($destinationDirId)) {
            $query = $query->and(Expression::isNull('destination_dir_id'))->build();
            $params = ['user_id' => $userId, 'filename' => $fileName];
        } else {
            $query = $query->and(Expression::equal('destination_dir_id'))->build();
            $params = ['user_id' => $userId, 'filename' => $fileName, 'destination_dir_id' => $destinationDirId];
        }
        $query .= ' AND (status=\'' . UploadSessionStatus::UPLOADING->value . '\' OR status=\'' . UploadSessionStatus::BUILDING->value . '\')';
        return $this->fetchOne($query, $params, PDO::FETCH_NUM)[0] != 0;
    }

    public function getUserSessionsCount(int $userId): int
    {
        $query = $this->queryBuilder
            ->count()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('status'))
            ->build();
        return $this->fetchOne($query, ['user_id' => $userId, 'status' => UploadSessionStatus::UPLOADING->value], PDO::FETCH_NUM)[0];
    }

    /**
     * @return UploadSession[]
     */
    public function getUserSessions(int $userId): array
    {
        $query = $this->queryBuilder->select()->where(Expression::equal('user_id'))->build();
        $data = $this->fetchAll($query, ['user_id' => $userId]);
        $res = [];
        foreach ($data as $session) {
            $res[] = UploadSession::createFromArr($session);
        }
        return $res;
    }

    public function setStatus(int $userId, int $sessionId, UploadSessionStatus $status)
    {
        $query = $this->queryBuilder
            ->update(['status'])
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))
            ->build();

        $this->update($query, ['user_id' => $userId, 'id' => $sessionId, 'status' => $status->value]);
    }
}
