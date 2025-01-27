<?php

namespace App\Repositories;

use App\Tools\QueryBuilder;
use App\Repositories\BaseRepository;

class FileSystemRepository extends BaseRepository
{
    /**
     * @return string new dir id
     */
    public function createDir(string $userId, string $dirName, string $path, string $parentDirId = null): string
    {
        $query = $this->queryBuilder->insert(['name', 'user_id', 'created_at', 'parent_id', 'type', 'path'])->build();
        $newDirId = $this->insertAndGetId($query, [
            'name' => $dirName,
            'user_id' => $userId,
            'created_at' => date('Y-m-d'),
            'parent_id' => $parentDirId,
            'type' => 'folder',
            'path' => $path
        ]);

        return $newDirId;
    }

    public function getDirPathById(string $dirId): null|string|false
    {
        $query = $this->queryBuilder->select(['path'])->where('id', QueryBuilder::EQUAL)->build();
        $data = $this->fetchOne($query, ['id' => $dirId]);

        if ($data === false) return false;
        else return $data['path'];
    }

    public function getDirContent(string $userId, string $dirId = null): array|false
    {
        if (is_null($dirId)) return $this->getRootContent($userId);
        else return $this->getConcreteDirContent($userId, $dirId);
    }

    private function getRootContent(string $userId): array|false
    {
        $query = $this->queryBuilder->select()->where('user_id', QueryBuilder::EQUAL)->and('parent_id', QueryBuilder::IS_NULL)->build();
        $content = $this->fetchAll($query, ['user_id' => $userId]);

        return $content;
    }

    private function getConcreteDirContent(string $userId, string $dirId): array|false
    {
        $query = $this->queryBuilder->select()->where('user_id', QueryBuilder::EQUAL)->and('parent_id', QueryBuilder::EQUAL)->build();
        $content = $this->fetchAll($query, ['user_id' => $userId, 'parent_id' => $dirId]);

        return $content;
    }
}
