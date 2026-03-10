<?php

namespace App\Repositories;

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
}
