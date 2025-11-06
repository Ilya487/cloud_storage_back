<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Models\FileSystemObject;
use App\Models\FsObjectType;
use App\Db\QueryBuilder;
use App\Repositories\BaseRepository;
use Exception;
use PDO;

class FileSystemRepository extends BaseRepository
{
    private bool $isOperationConfirm = true;
    /**
     * @return string new dir id
     */
    public function createDir(int $userId, string $dirName, string $path, int $parentDirId = null): string
    {
        $this->processOperationStatus();

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
    public function createFile(int $userId, string $fileName, string $path, ?int $parentDirId = null, int $fileSize): string
    {
        $this->processOperationStatus();

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
        $query = $this->queryBuilder
            ->select(['path'])
            ->where(Expression::equal('id'))
            ->and(Expression::equal('user_id'))
            ->build();
        $data = $this->fetchOne($query, ['id' => $id, 'user_id' => $userId]);

        if ($data === false) return false;
        else return $data['path'];
    }

    /**
     * @param string $path в конце не должно быть слеша
     * @param string $updatedPath в конце не должно быть слеша
     */
    public function rename(int $userId, FsObjectType $type, string $currentPath, string $updatedPath, string $newName)
    {
        if ($type == FsObjectType::DIR) {
            $this->processOperationStatus();

            $this->renameObject($userId, $currentPath, $updatedPath, $newName);
            $this->renameInnerFolders($userId, $currentPath, $updatedPath);
        } else if ($type == FsObjectType::FILE) {
            $this->processOperationStatus();

            $this->renameObject($userId, $currentPath, $updatedPath, $newName);
        } else throw new Exception('Unknown fs object type');
    }

    public function getDirContent(int $userId, ?int $dirId = null): array|false
    {
        if (is_null($dirId)) return $this->getRootContent($userId);
        else return $this->getConcreteDirContent($userId, $dirId);
    }

    public function checkDirExist(int $userId, int $dirId): bool
    {
        $query = $this->queryBuilder
            ->count()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('type'))
            ->and(Expression::equal('id'))
            ->build();
        $res = $this->fetchOne($query, ['user_id' => $userId, 'id' => $dirId, 'type' => 'folder'], PDO::FETCH_NUM);
        if ($res[0] == 0) return false;
        else return true;
    }

    public function deleteById(int $userId, int $itemId)
    {
        $this->processOperationStatus();

        $query = $this->queryBuilder
            ->delete()
            ->where(Expression::equal('id'))
            ->and(Expression::equal('user_id'))
            ->build();
        $this->delete($query, ['id' => $itemId, 'user_id' => $userId]);
    }

    public function moveObject(
        int $userId,
        FsObjectType $type,
        string $currentPath,
        string $updatedPath,
        ?int $toDirId = null
    ) {
        if ($type == FsObjectType::DIR) {
            $this->processOperationStatus();

            $this->moveTopItem($userId, $currentPath, $updatedPath, $toDirId);
            $this->moveInnerItems($userId, $currentPath, $updatedPath);
        } else if ($type == FsObjectType::FILE) {
            $this->processOperationStatus();

            $this->moveTopItem($userId, $currentPath, $updatedPath, $toDirId);
        } else throw new Exception('Unknown fs object type');
    }

    public function isNameExist(int $userId, string $name, ?int $dirId = null)
    {
        $query = $this->queryBuilder
            ->count()
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('name'));
        if (is_null($dirId)) {
            $query = $query->and(Expression::isNull('parent_id'))->build();
            $params = ['user_id' => $userId, 'name' => $name];
        } else {
            $query = $query->and(Expression::equal('parent_id'))->build();
            $params = ['user_id' => $userId, 'name' => $name, 'parent_id' => $dirId];
        }

        return $this->fetchOne($query, $params, PDO::FETCH_NUM)[0] != 0;
    }

    public function getTypeById(int $userId, int $fileId): string|false
    {
        $query = $this->queryBuilder
            ->select(['type'])
            ->where(Expression::equal('user_id'))
            ->and(Expression::equal('id'))->build();
        $res =  $this->fetchOne($query, ['user_id' => $userId, 'id' => $fileId], PDO::FETCH_NUM);
        if ($res === false) return false;
        else return $res[0];
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

    /**
     * @return FileSystemObject[]|false
     */
    public function getMany(int $userId, array $ids): array|false
    {
        $preparedIds = [];
        foreach ($ids as $key => $value) {
            $preparedIds[":$key"] = $value;
        }

        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::in('id', array_keys($ids)))
            ->build();

        $requestRes = $this->fetchAll($query, ['user_id' => $userId, ...$preparedIds]);
        if (empty($requestRes)) return false;

        $fsObjectsCollection = [];
        foreach ($requestRes as $raw) {
            $fsObjectsCollection[] = FileSystemObject::createFromArr($raw);
        }

        return $fsObjectsCollection;
    }

    public function confirmChanges()
    {
        $this->isOperationConfirm = true;
        $this->submitTransaction();
    }

    public function cancelLastChanges()
    {
        $this->isOperationConfirm = true;
        $this->rollBackTransaction();
    }

    private function processOperationStatus()
    {
        if (!$this->isOperationConfirm) {
            throw new Exception('You must confirm last operation');
        }
        $this->isOperationConfirm = false;
        $this->beginTransaction();
    }

    private function getRootContent(int $userId): array|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('user_id'))
            ->and(Expression::isNull('parent_id'))
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
            ->build();
        $content = $this->fetchAll($query, ['user_id' => $userId, 'parent_id' => $dirId]);

        return $content;
    }

    private function renameObject(int $userId, string $path, string $updatedPath, string $newName)
    {
        $query = $this->queryBuilder
            ->update(['path', 'name'])
            ->where(Expression::like('path', 'pathPattern'))
            ->and(Expression::equal('user_id'))
            ->build();
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
        $query = $this->queryBuilder
            ->update(['parent_id', 'path'])
            ->where(Expression::equal('user_id'))
            ->and(Expression::like('path', 'currentPath'))
            ->build();
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
