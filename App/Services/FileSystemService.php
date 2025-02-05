<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Repositories\FileSystemRepository;
use App\Storage\DiskStorage;

class FileSystemService
{
    public function __construct(private DiskStorage $diskStorage, private FileSystemRepository $fsRepo) {}

    public function createFolder(int $userId, string $dirName, ?int $parentDirId = null): ?OperationResult
    {
        $parentPath = $parentDirId ? $this->fsRepo->getPathById($parentDirId) : '';
        if ($parentPath === false) return new OperationResult(false, null, ['message' => 'Неверный айди родительского каталога']);

        if ($this->diskStorage->createDir($userId, $dirName, $parentPath)) {
            $path = "$parentPath/$dirName";
            $dirId = $this->fsRepo->createDir($userId, $dirName, $path, $parentDirId);
            return new OperationResult(true, ['dirId' => $dirId]);
        } else return new OperationResult(false, null, ['message' => 'Папка с таким именем уже существует']);
    }

    public function getFolderContent(int $userId, ?int $dirId = null): ?OperationResult
    {
        $pathToSelectedDir = is_null($dirId) ? '/' : $this->fsRepo->getPathById($dirId);
        if ($pathToSelectedDir === false) return new OperationResult(false, null, ['message' => 'Указан неверный айди']);

        $catalogData = $this->fsRepo->getDirContent($userId, $dirId);

        if ($catalogData !== false) return new OperationResult(true, ['path' => $pathToSelectedDir, 'contents' => $catalogData]);
        else return new OperationResult(false, null, ['message' => 'Неверный айди пользователя или каталога']);
    }

    public function initializeUserStorage(int $userId): bool
    {
        return $this->diskStorage->initializeUserFolder($userId);
    }

    public function renameFolder(int $userId, int $dirId, string $newName)
    {
        $folderPath = $this->fsRepo->getPathById($dirId);
        if ($folderPath === false) return new OperationResult(false, null, ['message' => 'Указан неверный айди']);

        if ($this->diskStorage->renameDir($userId, $newName, $folderPath)) {
            $lastSlashPos = strrpos($folderPath, '/', -1);
            $updatedPath = substr($folderPath, 0, $lastSlashPos + 1) . $newName;

            $this->fsRepo->renameDir($userId, $folderPath, $updatedPath, $newName);
            return new OperationResult(true, ['updatedPath' => $updatedPath]);
        } else {
            return new OperationResult(false, null, ['error' => 'Не удалось переименовать папку']);
        }
    }

    public function deleteFolder(int $userId, int $dirId)
    {
        $folderPath = $this->fsRepo->getPathById($dirId);
        if ($folderPath === false) return new OperationResult(false, null, ['message' => 'Указан неверный айди']);

        if ($this->diskStorage->deleteDir($userId, $folderPath)) {
            $this->fsRepo->deleteById($userId, $dirId);
            return new OperationResult(true, ['message' => 'Папка успешно удалена']);
        } else {
            return new OperationResult(false, null, ['message' => 'Не удалось удалить папку']);
        }
    }
}
