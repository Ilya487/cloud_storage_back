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

    public function getDirPathById(string $dirId): null|string|false
    {
        $query = $this->queryBuilder->select(['path'])->where('id', QueryBuilder::EQUAL)->build();
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['id' => $dirId]);
        $data =  $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data === false) return false;
        else return $data['path'];
    }

    public function getDirContent(string $userId, ?string $dirId): array|false
    {
        if (is_null($dirId)) return $this->getRootContent($userId);
        else return $this->getConcreteDirContent($userId, $dirId);
    }

    private function getRootContent(string $userId): array|false
    {
        $query = $this->queryBuilder->select()->where('user_id', QueryBuilder::EQUAL)->and('parent_id', QueryBuilder::IS_NULL)->build();
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getConcreteDirContent(string $userId, string $dirId): array|false
    {
        $query = $this->queryBuilder->select()->where('user_id', QueryBuilder::EQUAL)->and('parent_id', QueryBuilder::EQUAL)->build();
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['user_id' => $userId, 'parent_id' => $dirId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
