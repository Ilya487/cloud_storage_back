<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Repositories\FileSystemRepository;
use App\Storage\ArchiveStorage;
use App\Storage\DiskStorage;

class FileSystemService
{
    public function __construct(private DiskStorage $diskStorage, private FileSystemRepository $fsRepo, private ArchiveStorage $archiveStorage) {}

    public function createFolder(int $userId, string $dirName, ?int $parentDirId = null): OperationResult
    {
        $parentPath = $parentDirId ? $this->fsRepo->getPathById($parentDirId, $userId) : '';
        if ($parentPath === false) return new OperationResult(false, null, ['message' => 'Неверный айди родительского каталога']);

        if ($this->diskStorage->createDir($userId, $dirName, $parentPath)) {
            $path = "$parentPath/$dirName";
            $dirId = $this->fsRepo->createDir($userId, $dirName, $path, $parentDirId);
            return new OperationResult(true, ['dirId' => $dirId]);
        } else return new OperationResult(false, null, ['message' => 'Папка с таким именем уже существует']);
    }

    public function getFolderContent(int $userId, ?int $dirId = null): OperationResult
    {
        if ($this->fsRepo->getTypeById($userId, $dirId) == 'file')
            return new OperationResult(false, null, ['message' => 'Указан неверный айди']);

        $pathToSelectedDir = is_null($dirId) ? '/' : $this->fsRepo->getPathById($dirId, $userId);
        if ($pathToSelectedDir === false) return new OperationResult(false, null, ['message' => 'Указан неверный айди']);

        $catalogData = $this->fsRepo->getDirContent($userId, $dirId);

        if ($catalogData !== false) return new OperationResult(true, ['path' => $pathToSelectedDir, 'contents' => $catalogData]);
        else return new OperationResult(false, null, ['message' => 'Указан неверный айди']);
    }

    public function initializeUserStorage(int $userId): bool
    {
        return $this->diskStorage->initializeUserFolder($userId);
    }

    public function renameObject(int $userId, int $objectId, string $newName): OperationResult
    {
        $type = $this->fsRepo->getTypeById($userId, $objectId);
        if ($type === false) return new OperationResult(false, null, ['message' => 'Указан неверный айди']);

        $objectPath = $this->fsRepo->getPathById($objectId, $userId);
        if ($this->diskStorage->renameObject($userId, $newName, $objectPath)) {
            $parentDir = dirname($objectPath);
            $updatedPath = $parentDir == DIRECTORY_SEPARATOR ? '' . "/$newName" : $parentDir . "/$newName";

            if ($type == 'folder') $this->fsRepo->renameDir($userId, $objectPath, $updatedPath, $newName);
            if ($type == 'file') $this->fsRepo->renameFile($userId, $objectPath, $updatedPath, $newName);

            return new OperationResult(true, ['updatedPath' => $updatedPath]);
        } else {
            return new OperationResult(false, null, ['message' => 'Не удалось переименовать ' . ($type == 'folder' ? 'папку' : 'файл')]);
        }
    }

    public function deleteObject(int $userId, int $objectId): OperationResult
    {
        $type = $this->fsRepo->getTypeById($userId, $objectId);
        if ($type === false) return new OperationResult(false, null, ['message' => 'Указан неверный айди']);

        $objectPath = $this->fsRepo->getPathById($objectId, $userId);

        if ($type == 'folder') return $this->deleteFolder($userId, $objectId, $objectPath);
        else  return $this->deleteFile($userId, $objectId, $objectPath);
    }

    private function deleteFolder(int $userId, int $dirId, string $dirPath): OperationResult
    {
        if ($this->diskStorage->deleteDir($userId, $dirPath)) {
            $this->fsRepo->deleteById($userId, $dirId);
            return new OperationResult(true, ['message' => 'Папка успешно удалена']);
        } else {
            return new OperationResult(false, null, ['message' => 'Не удалось удалить папку']);
        }
    }

    private function deleteFile(int $userId, int $fileId, string $filePath): OperationResult
    {
        if ($this->diskStorage->deleteFile($userId, $filePath)) {
            $this->fsRepo->deleteById($userId, $fileId);
            return new OperationResult(true, ['message' => 'Файл успешно удален']);
        } else {
            return new OperationResult(false, null, ['message' => 'Не удалось удалить файл']);
        }
    }

    public function moveObject(int $userId, int $objectId, ?int $toDirId = null): OperationResult
    {
        $type = $this->fsRepo->getTypeById($userId, $objectId);
        if ($type === false) return new OperationResult(false, null, ['message' => 'Указан некорректный айди перемещаемого ресурса']);

        if ($objectId == $toDirId) {
            return new OperationResult(false, null, ['message' => 'Путь источника и назначения совпадают']);
        }

        $currentPath = $this->fsRepo->getPathById($objectId, $userId);
        if ($currentPath === false) {
            return new OperationResult(false, null, ['message' => 'Указан некорректный айди перемещаемого ресурса']);
        }

        $toDirPath = is_null($toDirId) ? '' : $this->fsRepo->getPathById($toDirId, $userId);
        if ($toDirPath === false) {
            return new OperationResult(false, null, ['message' => 'Указана некорректная папка назначения']);
        }

        $updatedPath = "$toDirPath/" . basename($currentPath);

        if ($currentPath == $updatedPath) {
            return new OperationResult(false, null, ['message' => 'Путь источника и назначения совпадают']);
        }

        if ($this->diskStorage->moveItem($userId, $currentPath, $toDirPath)) {
            if ($type == 'folder') $this->fsRepo->moveFolder($userId, $currentPath, $updatedPath, $toDirId);
            else $this->fsRepo->moveFile($userId, $currentPath, $updatedPath, $toDirId);

            return new OperationResult(true, ['updatedPath' => $updatedPath]);
        } else {
            return new OperationResult(false, null, [
                'message' => 'Не удалось переместить ' . ($type == 'folder' ? 'папку' : 'файл')
            ]);
        }
    }

    public function getPathForDownload(int $userId, int $fileId): OperationResult
    {
        $type =  $this->fsRepo->getTypeById($userId, $fileId);
        if (!$type) {
            return new OperationResult(false, null, ['message' => 'Объект с таким айди не найден', 'code' => 404]);
        }

        $partPath =  $this->fsRepo->getPathById($fileId, $userId);
        $fullPath = $this->diskStorage->getPath($userId, $partPath);

        if ($type == 'file') {
            return new OperationResult(true, ['path' => $fullPath, 'type' => 'file']);
        } else {
            $archivePath = $this->archiveStorage->createArchive($userId, $fileId, $fullPath);
            if (!$archivePath) return new OperationResult(false, null, ['message' => 'Не удалось создать архив для загрузки папки', 'code' => 500]);
            return new OperationResult(true, ['path' => $archivePath, 'type' => 'folder']);
        }
    }

    public function getDirIdByPath(int $userId, string $path): OperationResult
    {
        $path = str_replace('\\', '/', $path);

        if ($path == '/') return new OperationResult(true, ['dirId' => 'root']);

        $dirId = $this->fsRepo->getDirIdByPath($userId, $path);
        if ($dirId === false) return new OperationResult(false, null, ['message' => 'Папка с данным расположением не найдена']);
        else return new OperationResult(true, ['dirId' => $dirId]);
    }
}
