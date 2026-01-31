<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\PrepareFilesTask;
use App\Models\PrepareFilesTaskStatus;
use App\Repositories\BaseRepository;
use PDO;

class PreapareFilesTaskRepository extends BaseRepository
{
    public function createTask($userId, array $filesId): string
    {
        $query = $this->queryBuilder->insert(['user_id', 'files_id'])->build();
        $serializedArr = join(',', $filesId);
        return $this->insert($query, ['user_id' => $userId, 'files_id' => $serializedArr]);
    }

    public function getById(int $userId, int $taskId): PrepareFilesTask|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))->build();


        $res = $this->fetchOne($query, ['user_id' => $userId, 'id' => $taskId]);
        if ($res === false) return false;
        return PrepareFilesTask::createFromArr($res);
    }

    public function setStatus(int $userId, int $taskId, PrepareFilesTaskStatus $status)
    {
        $query = $this->queryBuilder
            ->update(['status'])
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))->build();
        $this->update($query, ['user_id' => $userId, 'id' => $taskId, 'status' => $status->value]);
    }

    public function getUserTaskCount(int $userId): int
    {
        $query = $this->queryBuilder
            ->count()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('status'))
            ->build();

        return $this->fetchOne(
            $query,
            ['user_id' => $userId, 'status' => PrepareFilesTaskStatus::PREPARING->value],
            PDO::FETCH_COLUMN
        );
    }
}
