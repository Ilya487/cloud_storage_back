<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\Collections\PrepareFilesTaskCollection;
use App\Models\PrepareFilesTask;
use App\Models\PrepareFilesTaskStatus;
use App\Repositories\BaseRepository;
use App\Repositories\UserRepository;
use App\Tools\DbConnect;

class PrepareFilesTaskRepository extends BaseRepository
{
    protected string $tableName = 'prepare_files_task';

    public function __construct(
        DbConnect $dbConnect,
        private UserRepository $userRepo
    ) {
        parent::__construct($dbConnect);
    }

    public function createTask($userId, array $filesId, int $limit, int $expiredAt): int|false
    {
        $this->beginTransaction();
        $canInsert = $this->userRepo->incrementDownloadSessionCount($userId, $limit);

        if (!$canInsert) {
            $this->rollBackTransaction();
            return false;
        }

        $query = $this->queryBuilder->insert(['user_id', 'files_id', 'expired_at'])->build();
        $serializedArr = join(',', $filesId);
        $taskId = $this->insert($query, [
            'user_id' => $userId,
            'files_id' => $serializedArr,
            'expired_at' => $expiredAt
        ]);
        $this->submitTransaction();
        return $taskId;
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

    public function getExpiredTasks(int $limit): PrepareFilesTaskCollection|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::less('expired_at', 'current_timestamp'))
            ->limit($limit)
            ->build();

        $res = $this->fetchAll($query, ['current_timestamp' => date('Y-m-d H:i:s')]);

        if (empty($res)) return false;
        return PrepareFilesTaskCollection::createFromDbArr($res);
    }

    public function deleteById(int $id)
    {
        $query = $this->queryBuilder->delete()
            ->where(Expression::equal('id'))
            ->build();

        $this->delete($query, ['id' => $id]);
    }
}
