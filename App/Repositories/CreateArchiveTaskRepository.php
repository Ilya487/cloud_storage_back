<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\Collections\CreateArchiveTaskCollection;
use App\Models\CreateArchiveTask;
use App\Models\CreateArchiveTaskStatus;
use App\Db\BaseRepository;
use App\Db\TransactionManager;
use App\Repositories\UserRepository;
use App\Tools\DbConnect;

class CreateArchiveTaskRepository extends BaseRepository
{
    protected string $tableName = 'prepare_files_task';

    public function __construct(
        DbConnect $dbConnect,
        TransactionManager $txManager,
        private UserRepository $userRepo
    ) {
        parent::__construct($dbConnect, $txManager);
    }

    public function createTask($userId, array $filesId, int $limit, int $expiredAt): int|false
    {
        $this->beginTransaction();
        $canInsert = $this->userRepo->incrementDownloadSessionCount($userId, $limit);

        if (!$canInsert) {
            $this->rollBackTransaction();
            return false;
        }

        $serializedArr = join(',', $filesId);
        $taskId = $this->insert([
            'user_id' => $userId,
            'files_id' => $serializedArr,
            'expired_at' => $this->formatTimestamp($expiredAt)
        ]);
        $this->submitTransaction();
        return $taskId;
    }

    public function getTaskById(int $userId, int $taskId): CreateArchiveTask|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->build();

        $res = $this->getById($taskId, $query);
        if ($res === false) return false;
        return CreateArchiveTask::createFromArr($res);
    }

    public function setStatus(int $userId, int $taskId, CreateArchiveTaskStatus $status)
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->build();

        $this->updateById($taskId, ['status' => $status->value], $query);
    }

    public function getExpiredTasks(int $limit): CreateArchiveTaskCollection|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::less('expired_at', date('Y-m-d H:i:s')))
            ->build();

        $res = $this->getAll(whereClauseQuery: $query, limit: $limit);

        if (empty($res)) return false;
        return CreateArchiveTaskCollection::createFromDbArr($res);
    }

    public function deleteTaskById(int $id)
    {
        $this->deleteById($id);
    }
}
