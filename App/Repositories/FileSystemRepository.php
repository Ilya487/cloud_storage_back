<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\Collections\FileSystemObjectCollection;
use App\Models\FileSystemObject;
use App\Repositories\BaseRepository;
use Exception;

class FileSystemRepository extends BaseRepository
{
    protected string $tableName = 'file_system';

    /**
     * @return string new dir id
     */
    public function createDir(int $userId, string $dirName, string $path, ?int $parentDirId): string
    {
        $query = $this->queryBuilder->insert(['name', 'user_id', 'created_at', 'parent_id', 'type', 'path'])->build();
        $this->beginTransaction();
        $newDirId = $this->insert($query, [
            'name' => $dirName,
            'user_id' => $userId,
            'created_at' => date('Y-m-d'),
            'parent_id' => $parentDirId,
            'type' => 'folder',
            'path' => $path
        ]);

        $query = $this->queryBuilder
            ->update(['path_ids'])
            ->where(Expression::equal('id'))
            ->build();
        $pathIds = is_null($parentDirId) ? "/$newDirId" : $this->getPathIds($userId, $parentDirId) . "/$newDirId";
        $this->update($query, ['id' => $newDirId, 'path_ids' => $pathIds]);
        $this->submitTransaction();

        return $newDirId;
    }

    /**
     * @return string new file id
     */
    public function createFile(int $userId, string $fileName, string $path, ?int $parentDirId, int $fileSize): string
    {
        $query = $this->queryBuilder->insert(['name', 'user_id', 'created_at', 'parent_id', 'type', 'path', 'size'])->build();
        $this->beginTransaction();

        $fileId = $this->insert($query, [
            'name' => $fileName,
            'user_id' => $userId,
            'created_at' => date('Y-m-d'),
            'parent_id' => $parentDirId,
            'type' => 'file',
            'path' => $path,
            'size' => $fileSize
        ]);

        $query = $this->queryBuilder
            ->update(['path_ids'])
            ->where(Expression::equal('id'))
            ->build();
        $pathIds = is_null($parentDirId) ? "/$fileId" : $this->getPathIds($userId, $parentDirId) . "/$fileId";
        $this->update($query, ['id' => $fileId, 'path_ids' => $pathIds]);
        $this->submitTransaction();

        return $fileId;
    }

    public function getPathById(int $id, int $userId): string|false
    {
        $query = $this->queryBuilder
            ->select(['path'])
            ->where(Expression::equal('id'))
            ->and(Expression::equal('user_id'))
            ->build();
        $data = $this->fetchOne($query, ['id' => $id, 'user_id' => $userId]);

        if ($data === false) return false;
        else return $data['path'];
    }

    public function rename(FileSystemObject $fsObject, string $newName)
    {
        $currentPath = $fsObject->getPath();
        $updatedPath = $fsObject->rename($newName);
        $id = $fsObject->id;
        $userId = $fsObject->ownerId;

        if (!$fsObject->isFile()) {
            $this->beginTransaction();
            $this->renameObject($userId, $id, $currentPath, $updatedPath, $newName);
            $this->renameInnerFolders($userId, $id, $currentPath, $updatedPath);
            $this->submitTransaction();
        } else if ($fsObject->isFile()) {
            $this->renameObject($userId, $id, $currentPath, $updatedPath, $newName);
        } else throw new Exception('Unknown fs object type');
    }

    public function getDirContent(int $userId, ?int $dirId = null): array|false
    {
        if (is_null($dirId)) return $this->getRootContent($userId);
        else return $this->getConcreteDirContent($userId, $dirId);
    }

    private function softDeleteFileById(int $userId, int $itemId)
    {
        $query = $this->queryBuilder
            ->update(['deleted_at'])
            ->where(Expression::equal('id'))
            ->and(Expression::equal('user_id'))
            ->build();
        $this->delete($query, ['id' => $itemId, 'user_id' => $userId, 'deleted_at' => date('Y-m-d H:i:s')]);
    }

    public function softDeleteObject(FileSystemObject $fsObject)
    {
        if ($fsObject->isFile()) {
            $this->softDeleteFileById($fsObject->ownerId, $fsObject->id);
            return;
        }
        $query = $this->queryBuilder
            ->update(['deleted_at'])
            ->where(Expression::equal('user_id'))
            ->and(Expression::like('path_ids', 'p'))
            ->build();
        $this->delete($query, [
            'deleted_at' => date('Y-m-d H:i:s'),
            'user_id' => $fsObject->ownerId,
            'p' => $fsObject->getPathIds() . '%'
        ]);
    }

    public function moveObject(FileSystemObject $fsObject, FileSystemObject $toDir)
    {
        $userId = $fsObject->ownerId;
        $currentPath = $fsObject->getPath();
        $currentPathIds = $fsObject->getPathIds();
        $updatedPath = $fsObject->changeDir($toDir);
        if ($updatedPath === false) return false;

        if (!$fsObject->isFile()) {
            $this->beginTransaction();
            $this->moveTopItem($userId, $currentPathIds, $updatedPath, $fsObject->getPathIds(), $toDir->id);
            $this->moveInnerItems($userId, $currentPath, $updatedPath, $currentPathIds, $fsObject->getPathIds());
            $this->submitTransaction();
        } else if ($fsObject->isFile()) {
            $this->moveTopItem($userId, $currentPathIds, $updatedPath, $fsObject->getPathIds(), $toDir->id);
        } else throw new Exception('Unknown fs object type');
    }

    public function getDirIdByPath(int $userId, string $path): int|false
    {
        $query = $this->queryBuilder
            ->select(['id'])
            ->where(Expression::equal('path'))
            ->and(Expression::equal('user_id'))
            ->and(Expression::equal('type'))
            ->build();
        $res = $this->fetchOne($query, ['user_id' => $userId, 'path' => $path, 'type' => 'folder']);

        if ($res === false) return false;
        else return $res['id'];
    }

    public function getById(int $userId, int $objectId): FileSystemObject|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))
            ->build();
        $res = $this->fetchOne($query, ['user_id' => $userId, 'id' => $objectId]);
        if ($res === false) return false;
        return FileSystemObject::createFromArr($res);
    }

    public function getMany(int $userId, array $ids): FileSystemObjectCollection|false
    {
        $preparedIds = $this->prepareParamsForIn($ids);

        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::in('id', count($ids)))
            ->build();

        $requestRes = $this->fetchAll($query, ['user_id' => $userId, ...$preparedIds]);
        if (empty($requestRes)) return false;

        return FileSystemObjectCollection::createFromDbArr($requestRes);
    }

    public function search(int $userId, string $searchQuery)
    {
        $qury = $this->queryBuilder
            ->select(['id', 'name', 'parent_id', 'type', 'path'])
            ->where(Expression::equal('user_id'))
            ->and(Expression::like('name', 'pattern'))
            ->and(Expression::isNull('deleted_at'))
            ->build();
        $res = $this->fetchAll($qury, ['user_id' => $userId, 'pattern' => "%$searchQuery%"]);
        return $res;
    }

    public function getFileTreeByIds(int $userId, array $ids): FileSystemObjectCollection|false
    {
        $query = $this->queryBuilder->resetQuery()
            ->where(Expression::in('id', count($ids)))
            ->and(Expression::equal('user_id'))
            ->build();

        $query = $this->getRecursiveCTE($query);
        $query .= "
        SELECT * FROM {$this->tableName} WHERE id IN (SELECT id FROM file_tree)";

        $res = $this->fetchAll($query, ['user_id' => $userId, ...$this->prepareParamsForIn($ids)]);
        if (empty($res)) return false;
        return FileSystemObjectCollection::createFromDbArr($res);
    }

    public function getDeletedFiles(int $userId)
    {
        $query = $this->queryBuilder
            ->select([])
            ->where(Expression::equal('user_id'))
            ->and(Expression::notNull('deleted_at'))
            ->build();
        return $this->fetchAll($query, ['user_id' => $userId]);
    }

    public function restoreObject(FileSystemObject $fsObject)
    {
        if ($fsObject->isFile()) {
            $query = $this->queryBuilder
                ->update(['deleted_at'])
                ->where(Expression::equal('id'))
                ->and(Expression::equal('user_id'))
                ->build();

            $this->update($query, ['deleted_at' => null, 'user_id' => $fsObject->ownerId, 'id' => $fsObject->id]);
        } else {
            $query = $this->queryBuilder
                ->update(['deleted_at'])
                ->where(Expression::like('path_ids', 'p'))
                ->and(Expression::equal('user_id'))
                ->build();

            $this->update($query, [
                'deleted_at' => null,
                'user_id' => $fsObject->ownerId,
                'p' => $fsObject->getPathIds() . '%'
            ]);
        }
    }

    private function getRecursiveCTE(string $whereClause = '', int $depth = 0): string
    {
        $depthLimit = $depth == 0 ? '' : "<$depth";

        return "WITH RECURSIVE file_tree AS (
            SELECT id, 1 AS 'depth'
            FROM {$this->tableName} $whereClause
            UNION
            SELECT t.id, tree.depth+1
            FROM {$this->tableName} t
            INNER JOIN file_tree tree ON t.parent_id = tree.id
            WHERE tree.depth $depthLimit
        )";
    }

    private function getRootContent(int $userId): array|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::isNull('parent_id'))
            ->and(Expression::isNull('deleted_at'))
            ->build();
        $content = $this->fetchAll($query, ['user_id' => $userId]);

        return $content;
    }

    private function getConcreteDirContent(int $userId, int $dirId): array|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('parent_id'))
            ->and(Expression::isNull('deleted_at'))
            ->build();
        $content = $this->fetchAll($query, ['user_id' => $userId, 'parent_id' => $dirId]);

        return $content;
    }

    private function renameObject(int $userId, int $id, string $path, string $updatedPath, string $newName)
    {
        $query = $this->queryBuilder
            ->update(['path', 'name'])
            ->where(Expression::like('path', 'pathPattern'))
            ->and(Expression::equal('user_id'))
            ->and(Expression::equal('id'))
            ->build();
        $this->update($query, [
            'path' => $updatedPath,
            'name' => $newName,
            'pathPattern' => $path,
            'user_id' => $userId,
            'id' => $id
        ]);
    }

    private function renameInnerFolders(int $userId, int $id, string $path, string $updatedPath)
    {
        $startPos = mb_strlen($path) + 1;

        $query = $this->getRecursiveCTE(
            $this->queryBuilder->resetQuery()
                ->where(Expression::equal('id', 'dirId'))
                ->and(Expression::equal('user_id', 'userId'))
                ->build()
        );
        $query .= "
        UPDATE {$this->tableName}
        SET path = CONCAT(:updatedPath, SUBSTRING(path, $startPos))
        WHERE id IN (SELECT id FROM file_tree) AND id <> :dirId;
        ";

        $this->update($query, [
            'updatedPath' => $updatedPath,
            'userId' => $userId,
            'dirId' => $id
        ]);
    }

    private function moveTopItem(int $userId, string $currentPathIds, string $updatedPath, string $updatedPathIds, ?int $toDirId)
    {
        $query = $this->queryBuilder
            ->update(['parent_id', 'path', 'path_ids'])
            ->where(Expression::equal('user_id'))
            ->and(Expression::like('path_ids', 'currentPathIds'))
            ->build();
        $this->update($query, [
            'parent_id' => $toDirId,
            'path' => $updatedPath,
            'user_id' => $userId,
            'currentPathIds' => $currentPathIds,
            'path_ids' => $updatedPathIds
        ]);
    }

    private function moveInnerItems(int $userId, string $currentPath, string $updatedPath, string $currentPathIds, string $updatedPathIds)
    {
        $currentPathLen = mb_strlen($currentPath) + 1;
        $currentPathIdsLen = mb_strlen($currentPathIds) + 1;
        $query = "
            UPDATE file_system
            SET path = CONCAT(:updatedPath, SUBSTR(path, $currentPathLen)),
            path_ids = CONCAT(:updatedPathIds, SUBSTR(path_ids, $currentPathIdsLen))
            WHERE path_ids LIKE :pathPattern AND user_id = :user_id;
        ";

        $this->update($query, [
            'updatedPath' => $updatedPath,
            'pathPattern' => $currentPathIds . '/%',
            'updatedPathIds' => $updatedPathIds,
            'user_id' => $userId
        ]);
    }

    private function getPathIds(int $userId, int $dirId): string
    {
        $query = $this->queryBuilder
            ->select(['path_ids'])
            ->where(Expression::equal('id'))
            ->and(Expression::equal('user_id'))
            ->build();

        $res = $this->fetchColumn($query, ['id' => $dirId, 'user_id' => $userId]);
        return $res;
    }
}
