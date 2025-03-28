<?php

namespace App\Repositories;

use App\Tools\QueryBuilder;
use App\Repositories\BaseRepository;
use PDO;

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

    /**
     * @return string new file id
     */
    public function createFile(int $userId, string $fileName, string $path, int $parentDirId = null, int $fileSize): string
    {
        $query = $this->queryBuilder->insert(['name', 'user_id', 'created_at', 'parent_id', 'type', 'path', 'size'])->build();
        $fileId = $this->insert($query, [
            'name' => $fileName,
            'user_id' => $userId,
            'created_at' => date('Y-m-d'),
            'parent_id' => $parentDirId,
            'type' => 'file',
            'path' => $path,
            'size' => $fileSize
        ]);

        return $fileId;
    }

    public function getPathById(int $id, int $userId): string|false
    {
        $query = $this->queryBuilder->select(['path'])->where('id', QueryBuilder::EQUAL)->and('user_id', QueryBuilder::EQUAL)->build();
        $data = $this->fetchOne($query, ['id' => $id, 'user_id' => $userId]);

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
        $this->renameObject($userId, $path, $updatedPath, $newName);
        $this->renameInnerFolders($userId, $path, $updatedPath);
        $this->submitTransaction();
    }

    /**
     * @param string $updatedPath в конце не должно быть слеша
     */
    public function renameFile(int $userId, string $path, string $updatedPath, string $newName)
    {
        $this->renameObject($userId, $path, $updatedPath, $newName);
    }

    public function getDirContent(int $userId, int $dirId = null): array|false
    {
        if (is_null($dirId)) return $this->getRootContent($userId);
        else return $this->getConcreteDirContent($userId, $dirId);
    }

    public function checkDirExist(int $userId, int $dirId)
    {
        $query = $this->queryBuilder->count()->where('user_id', '=')->and('type', '=')->and('id', '=')->build();
        $res = $this->fetchOne($query, ['user_id' => $userId, 'id' => $dirId, 'type' => 'folder'], PDO::FETCH_NUM);
        if ($res[0] == 0) return false;
        else return true;
    }

    public function deleteById(int $userId, int $itemId)
    {
        $query = $this->queryBuilder->delete()->where('id', QueryBuilder::EQUAL)->and('user_id', QueryBuilder::EQUAL)->build();
        $this->delete($query, ['id' => $itemId, 'user_id' => $userId]);
    }

    public function moveFolder(int $userId, string $currentPath, string $updatedPath, ?int $toDirId = null)
    {
        $this->beginTransaction();
        $this->moveTopItem($userId, $currentPath, $updatedPath, $toDirId);
        $this->moveInnerItems($userId, $currentPath, $updatedPath);
        $this->submitTransaction();
    }

    public function isNameExist(int $userId, string $name, ?int $dirId = null)
    {
        $query = $this->queryBuilder->count()->where('user_id', '=')->and('name', '=');
        if (is_null($dirId)) {
            $query = $query->and('parent_id', QueryBuilder::IS_NULL)->build();
            $params = ['user_id' => $userId, 'name' => $name];
        } else {
            $query = $query->and('parent_id', '=')->build();
            $params = ['user_id' => $userId, 'name' => $name, 'parent_id' => $dirId];
        }

        return $this->fetchOne($query, $params, PDO::FETCH_NUM)[0] != 0;
    }

    public function getTypeById(int $userId, int $fileId): string|false
    {
        $query = $this->queryBuilder->select(['type'])->where('user_id', '=')->and('id', '=')->build();
        $res =  $this->fetchOne($query, ['user_id' => $userId, 'id' => $fileId], PDO::FETCH_NUM);
        if ($res === false) return false;
        else return $res[0];
    }

    private function getRootContent(int $userId): array|false
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

    private function renameObject(int $userId, string $path, string $updatedPath, string $newName)
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

    private function moveTopItem(int $userId, string $currentPath, string $updatedPath, ?int $toDirId)
    {
        $query = $this->queryBuilder->update(['parent_id', 'path'])->where('user_id', QueryBuilder::EQUAL)->and('path', QueryBuilder::LIKE, 'currentPath')->build();
        $this->update($query, [
            'parent_id' => $toDirId,
            'path' => $updatedPath,
            'user_id' => $userId,
            'currentPath' => $currentPath
        ]);
    }

    private function moveInnerItems(int $userId, string $currentPath, string $updatedPath)
    {
        $currentPathLen = mb_strlen($currentPath) + 1;
        $query = "UPDATE file_system
        SET path = CONCAT(:updatedPath, SUBSTR(path, $currentPathLen))
        WHERE path LIKE :pathPattern AND user_id = :user_id;
        ";

        $this->update($query, [
            'updatedPath' => $updatedPath,
            'pathPattern' => $currentPath . '/%',
            'user_id' => $userId
        ]);
    }
}
