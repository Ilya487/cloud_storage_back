<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Repositories\BaseRepository;

class DownloadSessionsCountRepository extends BaseRepository
{
    protected string $tableName = 'download_sessions_count';

    public function getCountForUpdate(int $userId)
    {
        $query = $this->queryBuilder
            ->select(['count'])
            ->where(Expression::equal('user_id'))
            ->forUpdate()
            ->build();

        $res = $this->fetchColumn($query, ['user_id' => $userId]);
        return $res;
    }

    public function increment(int $userId)
    {
        $query = $this->queryBuilder
            ->incrementField('count')
            ->where(Expression::equal('user_id'))
            ->build();
        $this->update($query, ['user_id' => $userId]);
    }

    public function decrement(int $userId)
    {
        $query = $this->queryBuilder
            ->decrementField('count')
            ->where(Expression::equal('user_id'))
            ->build();
        $this->update($query, ['user_id' => $userId]);
    }
}
