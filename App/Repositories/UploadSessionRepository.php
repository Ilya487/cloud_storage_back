<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\Collections\UploadSessionCollection;
use App\Models\UploadSession;
use App\Models\UploadSessionStatus;
use App\Db\BaseRepository;

class UploadSessionRepository  extends BaseRepository
{
    protected string $tableName = 'upload_sessions';

    public function createUploadSession(
        int $userId,
        string $fileName,
        int $totalChunks,
        ?string $destinationDirPath,
        ?int $destinationDirId,
        int $fileSize,
        int $expireAt
    ): UploadSession {
        $id =  $this->insert([
            'user_id' => $userId,
            'filename' => $fileName,
            'destination_dir_path' => $destinationDirPath,
            'destination_dir_id' => $destinationDirId,
            'total_chunks' => $totalChunks,
            'file_size' => $fileSize,
            'expire_at' => $this->formatTimestamp($expireAt)
        ]);

        return UploadSession::createFromArr([
            'id' => $id,
            'user_id' => $userId,
            'filename' => $fileName,
            'destination_dir_path' => $destinationDirPath,
            'total_chunks' => $totalChunks,
            'file_size' => $fileSize,
            'completed_chunks' => 0,
            'status' => UploadSessionStatus::UPLOADING->value,
            'expire_at' => $this->formatTimestamp($expireAt)
        ]);
    }

    public function deleteSession(UploadSession $session)
    {
        $this->deleteById($session->id);
    }

    public function getSessionById(int $userId, int $sessionId): UploadSession|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->build();

        $data = $this->getById($sessionId, $query);
        return $data == false ? false : UploadSession::createFromArr($data);
    }

    public function incrementCompletedChunks(int $uploadSessionId): int|false
    {
        $updateQuery = $this->queryBuilder
            ->add('completed_chunks', 1)
            ->where(Expression::equal('id', $uploadSessionId))
            ->build();

        $this->beginTransaction();
        $this->query($updateQuery);
        $count = $this->getById($uploadSessionId, fieldsForSelect: ['completed_chunks']);
        $this->submitTransaction();

        return $count;
    }

    public function getUserSessionsCount(int $userId): int
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::equal('status', UploadSessionStatus::UPLOADING->value))
            ->build();

        return $this->count($query);
    }

    public function getSessionsByIds(int $userId, array $ids): UploadSessionCollection
    {
        $query = $this->queryBuilder
            ->where(Expression::equal('user_id', $userId))
            ->where(Expression::in('id', $ids))
            ->build();

        $res = $this->getAll(whereClauseQuery: $query);
        if ($res === false) return UploadSessionCollection::createFromDbArr([]);
        else return UploadSessionCollection::createFromDbArr($res);
    }

    public function lockSession(int $sessionId)
    {
        $query = $this->queryBuilder
            ->lockForUpdate()
            ->where(Expression::equal('id', $sessionId))
            ->build();

        $this->query($query);
    }

    public function setStatus(int $userId, int $sessionId, UploadSessionStatus $status)
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->build();

        $this->updateById($sessionId, ['status' => $status->value], $query);
    }

    public function deleteSessionsByIds(array $ids)
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::in('id', $ids))
            ->build();

        return $this->delete($query);
    }

    public function getExpired(int $limit): UploadSessionCollection
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::less('expire_at', $this->formatTimestamp(time())))
            ->build();

        $res = $this->getAll([], $query, $limit);
        return UploadSessionCollection::createFromDbArr($res === false ? [] : $res);
    }
}
