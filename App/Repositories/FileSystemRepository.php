<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\Collections\FileSystemObjectCollection;
use App\Models\FileSystemObject;
use App\Db\BaseRepository;
use App\Db\Query;
use Exception;

class FileSystemRepository extends BaseRepository
{
    protected string $tableName = 'file_system';

    /**
     * @return string new dir id
     */
    public function createDir(int $userId, string $dirName, string $path, ?int $parentDirId = null): string
    {
        $this->beginTransaction();
        $newDirId = $this->insert([
            'name' => $dirName,
            'user_id' => $userId,
            'created_at' => date('Y-m-d'),
            'parent_id' => $parentDirId,
            'type' => 'folder',
            'path' => $path
        ]);

        $pathIds = is_null($parentDirId) ? "/$newDirId" : $this->getPathIds($userId, $parentDirId) . "/$newDirId";
        $this->updateById($newDirId, ['path_ids' => $pathIds]);
        $this->submitTransaction();

        return $newDirId;
    }

    /**
     * @return string new file id
     */
    public function createFile(int $userId, string $fileName, string $path, ?int $parentDirId, int $fileSize): string
    {
        $fileId = $this->insert([
            'name' => $fileName,
            'user_id' => $userId,
            'created_at' => date('Y-m-d'),
            'parent_id' => $parentDirId,
            'type' => 'file',
            'path' => $path,
            'size' => $fileSize
        ]);

        $pathIds = is_null($parentDirId) ? "/$fileId" : $this->getPathIds($userId, $parentDirId) . "/$fileId";
        $this->updateById($fileId, ['path_ids' => $pathIds]);
        $this->submitTransaction();

        return $fileId;
    }

    public function getPathById(int $id, int $userId): string|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('id', $id))
            ->and(Expression::equal('user_id', $userId))
            ->build();

        return $this->getOne(['path'], $query);
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
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('id', $itemId))
            ->and(Expression::equal('user_id', $userId))
            ->build();

        $this->updateById($itemId, ['deleted_at' => date('Y-m-d H:i:s')], $query);
    }

    public function softDeleteObject(FileSystemObject $fsObject)
    {
        if ($fsObject->isFile()) {
            $this->softDeleteFileById($fsObject->ownerId, $fsObject->id);
            return;
        }

        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $fsObject->ownerId))
            ->and(Expression::like('path_ids', $fsObject->getPathIds() . '%'))
            ->build();

        $this->update(['deleted_at' => date('Y-m-d H:i:s'),], $query);
    }

    public function moveObject(FileSystemObject $fsObject, FileSystemObject $toDir)
    {
        $userId = $fsObject->ownerId;
        $currentPath = $fsObject->getPath();
        $currentPathIds = $fsObject->getPathIds();
        $updatedPath = $fsObject->changeDir($toDir);
        if ($updatedPath === false) return false;

        if ($fsObject->isDir()) {
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
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('path', $path))
            ->and(Expression::equal('user_id', $userId))
            ->and(Expression::equal('type', 'folder'))
            ->build();

        return $this->getOne(['id'], $query);
    }

    public function getObjectById(int $userId, int $objectId): FileSystemObject|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->build();

        $res = $this->getById($objectId, $query);
        if ($res === false) return false;
        return FileSystemObject::createFromArr($res);
    }

    public function getMany(int $userId, array $ids): FileSystemObjectCollection|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::in('id', $ids))
            ->build();

        $requestRes = $this->getAll(whereClauseQuery: $query);
        if ($requestRes === false) return false;

        return FileSystemObjectCollection::createFromDbArr($requestRes);
    }

    public function search(int $userId, string $searchQuery): array|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::like('name', "%$searchQuery%", 'pattern'))
            ->and(Expression::isNull('deleted_at'))
            ->build();

        return $this->getAll(whereClauseQuery: $query);
    }

    public function getFileTreeByIds(int $userId, array $ids): FileSystemObjectCollection|false
    {
        $cteWhereClause = $this->queryBuilder->newQuery()
            ->where(Expression::in('id', $ids))
            ->and(Expression::equal('user_id', $userId))
            ->build();

        $query = $this->getRecursiveCTE($cteWhereClause->query);

        $selectQuery = $this->queryBuilder
            ->select()
            ->whereRaw('id IN (SELECT id FROM file_tree)')
            ->build();

        $query .= $selectQuery->query;

        $res = $this->query(new Query($query, array_merge($cteWhereClause->params, $selectQuery->params)))->data;
        if (empty($res)) return false;
        return FileSystemObjectCollection::createFromDbArr($res);
    }

    public function getDeletedFiles(int $userId)
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::notNull('deleted_at'))
            ->build();

        return $this->getAll(whereClauseQuery: $query);
    }

    public function restoreObject(FileSystemObject $fsObject)
    {
        if ($fsObject->isFile()) {
            $query = $this->queryBuilder->newQuery()
                ->and(Expression::equal('user_id', $fsObject->ownerId))
                ->build();

            $this->updateById($fsObject->id, ['deleted_at' => null], $query);
        } else {
            $query = $this->queryBuilder->newQuery()
                ->where(Expression::like('path_ids', $fsObject->getPathIds() . '%'))
                ->and(Expression::equal('user_id', $fsObject->ownerId))
                ->build();

            $this->update([
                'deleted_at' => null,
            ], $query);
        }
    }

    public function deletePermanently(int $userId, array $ids)
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::in('id', $ids))
            ->build();

        $this->delete($query);
    }

    private function getRecursiveCTE(string $whereClause = '', int $depth = 0): string
    {
        $depthLimit = $depth == 0 ? '' : "<$depth";
        $whereClause = $whereClause == '' ? '' : 'WHERE ' . $whereClause;

        return "WITH RECURSIVE file_tree AS (
            SELECT id, 1 AS 'depth'
            FROM {$this->tableName} $whereClause
            UNION
            SELECT t.id, tree.depth+1
            FROM {$this->tableName} t
            INNER JOIN file_tree tree ON t.parent_id = tree.id
            WHERE tree.depth $depthLimit
        )
        ";
    }

    private function getRootContent(int $userId): array|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::isNull('parent_id'))
            ->and(Expression::isNull('deleted_at'))
            ->build();

        return $this->getAll(whereClauseQuery: $query);
    }

    private function getConcreteDirContent(int $userId, int $dirId): array|false
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::equal('parent_id', $dirId))
            ->and(Expression::isNull('deleted_at'))
            ->build();

        return $this->getAll(whereClauseQuery: $query);
    }

    private function renameObject(int $userId, int $id, string $path, string $updatedPath, string $newName)
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::like('path', $path, 'pathPattern'))
            ->and(Expression::equal('user_id', $userId))
            ->build();

        $this->updateById($id, [
            'path' => $updatedPath,
            'name' => $newName,
        ], $query);
    }

    private function renameInnerFolders(int $userId, int $id, string $path, string $updatedPath)
    {
        $startPos = mb_strlen($path) + 1;

        $cteWhereClause = $this->queryBuilder->newQuery()
            ->where(Expression::equal('id', $id, 'dirId'))
            ->and(Expression::equal('user_id', $userId, 'userId'))
            ->build();

        $cteQuery = $this->getRecursiveCTE($cteWhereClause->query);
        $updateQuery = $this->queryBuilder
            ->update([
                'path' => Expression::raw("CONCAT(:updatedPath, SUBSTRING(path, $startPos))", ['updatedPath' => $updatedPath])
            ])
            ->where(Expression::notEqual('id', $id, 'dirId'))
            ->whereRaw('id IN (SELECT id FROM file_tree)')
            ->build();


        $this->query(new Query(
            $cteQuery . $updateQuery->query,
            array_merge($cteWhereClause->params, $updateQuery->params)
        ));
    }

    private function moveTopItem(int $userId, string $currentPathIds, string $updatedPath, string $updatedPathIds, ?int $toDirId)
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::like('path_ids', $currentPathIds, 'currentPathIds'))
            ->build();

        $this->update([
            'parent_id' => $toDirId,
            'path' => $updatedPath,
            'path_ids' => $updatedPathIds
        ], $query);
    }

    private function moveInnerItems(int $userId, string $currentPath, string $updatedPath, string $currentPathIds, string $updatedPathIds)
    {
        $currentPathLen = mb_strlen($currentPath) + 1;
        $currentPathIdsLen = mb_strlen($currentPathIds) + 1;

        $query = $this->queryBuilder
            ->update([
                'path' => Expression::raw("CONCAT(:updatedPath, SUBSTR(path, $currentPathLen))", ['updatedPath' => $updatedPath]),
                'path_ids' => Expression::raw("CONCAT(:updatedPathIds, SUBSTR(path_ids, $currentPathIdsLen))", ['updatedPathIds' => $updatedPathIds])
            ])
            ->where(Expression::equal('user_id', $userId))
            ->and(Expression::like('path_ids', $currentPathIds . '/%', 'pathPattern'))
            ->build();

        $this->query($query);
    }

    private function getPathIds(int $userId, int $dirId): string
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('id', $dirId))
            ->and(Expression::equal('user_id', $userId))
            ->build();

        return $this->getOne(['path_ids'], $query);
    }
}
