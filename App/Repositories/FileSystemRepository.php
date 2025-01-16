<?php

namespace App\Repositories;

use App\Tools\DbConnect;
use App\Tools\QueryBuilder;
use PDO;

class FileSystemRepository
{
    private PDO $pdo;
    private QueryBuilder $queryBuilder;


    public function __construct(DbConnect $dbConnect)
    {
        $this->pdo = $dbConnect->getConnection();
        $this->queryBuilder = new QueryBuilder('file_system');
    }

    /**
     * @return string new dir id
     */
    public function createDir(string $userId, string $dirName, string $path, string $parentDirId = null): string
    {
        $query = $this->queryBuilder->insert(['name', 'user_id', 'created_at', 'parent_id', 'type', 'path'])->build();
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'name' => $dirName,
            'user_id' => $userId,
            'created_at' => date('Y-m-d'),
            'parent_id' => $parentDirId,
            'type' => 'folder',
            'path' => $path
        ]);

        return $this->pdo->lastInsertId();
    }

    public function getDirPath(string $dirId): null|string|false
    {
        $query = $this->queryBuilder->select(['path'])->where('id', QueryBuilder::EQUAL)->build();
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['id' => $dirId]);
        $data =  $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data === false) return false;
        else return $data['path'];
    }
}
