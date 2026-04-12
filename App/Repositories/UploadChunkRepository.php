<?php

namespace App\Repositories;

use App\Db\BaseRepository;
use App\Db\Expression;
use PDOException;

class UploadChunkRepository extends BaseRepository
{
    protected string $tableName = 'upload_chunks_nums';

    public function insertChunk(int $sessionId, int $chunkNum): bool
    {
        $constraintErrorCode = '23000';
        $mysqlDuplicateErrorCode = 1062;

        try {
            $this->insert([
                'session_id' => $sessionId,
                'chunk_num' => $chunkNum
            ]);

            return true;
        } catch (PDOException $err) {
            if (
                isset($err->errorInfo[0], $err->errorInfo[1]) &&
                $err->errorInfo[0] === $constraintErrorCode &&
                $err->errorInfo[1] === $mysqlDuplicateErrorCode
            ) {
                return false;
            }
            throw $err;
        }
    }

    public function getSessionChunks(int $sessionId): array|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('session_id', $sessionId))
            ->build();

        $res = $this->getAll(['chunk_num'], $query);
        if ($res !== false)
            return array_column($res, 'chunk_num');
        return $res;
    }
}
