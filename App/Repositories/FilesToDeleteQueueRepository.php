<?php

namespace App\Repositories;

use App\Db\Expression;
use PDO;

class FilesToDeleteQueueRepository extends BaseRepository
{
    protected string $tableName = 'files_to_delete';

    public function setFilesInQueue(array $ids)
    {
        $query = $this->queryBuilder
            ->insertMany(['file_id'], $ids)
            ->build();

        $this->insertMany($query, $ids);
    }

    public function getIds(int $limit): array|false
    {
        $query = $this->queryBuilder
            ->select()
            ->limit($limit)
            ->build();

        $res = $this->fetchAll($query, [], PDO::FETCH_COLUMN);
        if (empty($res)) return false;
        else return $res;
    }

    public function deleteIds(array $ids)
    {
        $query = $this->queryBuilder
            ->delete()
            ->where(Expression::in('file_id', count($ids)))
            ->build();

        $this->delete($query, $this->prepareParamsForIn($ids));
    }
}
