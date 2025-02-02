<?php

namespace App\Repositories;

use App\Tools\QueryBuilder;
use App\Repositories\BaseRepository;

class FileSystemRepository extends BaseRepository
{
    /**
     * @return string new dir id
     */
    public function createDir(int $userId, string $dirName, string $path, int $parentDirId = null): string
    {
        $query = $this->queryBuilder->insert(['name', 'user_id', 'created_at', 'parent_id', 'type', 'path'])->build();
        $newDirId = $this->insert($query, [
            'name' => $dirName,
            'user_id' => $userId,
            'created_at' => date('Y-m-d'),
            'parent_id' => $parentDirId,
            'type' => 'folder',
            'path' => $path
        ]);

        return $newDirId;
    }

    public function getDirPathById(int $dirId): null|string|false
    {
        $query = $this->queryBuilder->select(['path'])->where('id', QueryBuilder::EQUAL)->build();
        $data = $this->fetchOne($query, ['id' => $dirId]);

        if ($data === false) return false;
        else return $data['path'];
    }

    /**
     * @param string $path в конце не должно быть слеша
     * @param string $updatedPath в конце не должно быть слеша
     */
    public function renameDir(int $userId, string $path, string $updatedPath, string $newName)
    {
        $this->beginTransaction();
        $this->renameOneFolder($userId, $path, $updatedPath, $newName);
        $this->renameInnerFolders($userId, $path, $updatedPath);
        $this->submitTransaction();
    }

    public function getDirContent(int $userId, int $dirId = null): array|false
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

    private function getConcreteDirContent(int $userId, int $dirId): array|false
    {
        $query = $this->queryBuilder->select()->where('user_id', QueryBuilder::EQUAL)->and('parent_id', QueryBuilder::EQUAL)->build();
        $content = $this->fetchAll($query, ['user_id' => $userId, 'parent_id' => $dirId]);

        return $content;
    }

    private function renameOneFolder(int $userId, string $path, string $updatedPath, string $newName)
    {
        $query = $this->queryBuilder->update(['path', 'name'])->where('path', QueryBuilder::LIKE, 'pathPattern')->and('user_id', QueryBuilder::EQUAL)->build();
        $this->update($query, ['path' => $updatedPath, 'name' => $newName, 'pathPattern' => $path, 'user_id' => $userId]);
    }

    private function renameInnerFolders(int $userId, string $path, string $updatedPath)
    {
        $startPos = mb_strlen($path) + 1;

        $query = "UPDATE file_system
        SET path = CONCAT(:updatedPath, SUBSTRING(path, $startPos))
        WHERE path LIKE :oldPath AND user_id = :user_id;
        ";

        $this->update($query, [
            'updatedPath' => $updatedPath,
            'oldPath' => $path . '/%',
            'user_id' => $userId,
        ]);
    }
}
