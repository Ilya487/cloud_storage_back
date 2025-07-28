<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Repositories\FileSystemRepository;
use App\Storage\ArchiveStorage;
use App\Storage\DiskStorage;
use App\UseCases\MoveFiles\MoveFilesResult;
use App\UseCases\MoveFiles\MoveFilesUseCase;

class FileSystemService
{
    public function __construct(
        private DiskStorage $diskStorage,
        private FileSystemRepository $fsRepo,
        private ArchiveStorage $archiveStorage,
        private MoveFilesUseCase $moveFiles,
        private DeleteFilesUseCase $deleteFiles
    ) {}

    public function createFolder(int $userId, string $dirName, ?int $parentDirId = null): OperationResult
    {
        $parentPath = $parentDirId ? $this->fsRepo->getPathById($parentDirId, $userId) : '';
        if ($parentPath === false) return new OperationResult(false, null, ['message' => 'Неверный айди родительского каталога']);

        $path = "$parentPath/$dirName";
        $dirId = $this->fsRepo->createDir($userId, $dirName, $path, $parentDirId);
        if ($this->diskStorage->createDir($userId, $dirName, $parentPath)) {
            $this->fsRepo->confirmChanges();
            return new OperationResult(true, ['dirId' => $dirId]);
        } else {
            $this->fsRepo->cancelLastChanges();
            return new OperationResult(false, null, ['message' => 'Папка с таким именем уже существует']);
        }
    }

    public function getFolderContent(int $userId, ?int $dirId = null): OperationResult
    {
        if (!is_null($dirId) && $this->fsRepo->getTypeById($userId, $dirId) == 'file')
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
        $parentDir = dirname($objectPath);
        $updatedPath = $parentDir == DIRECTORY_SEPARATOR ? '' . "/$newName" : $parentDir . "/$newName";

        if ($type == 'folder') $this->fsRepo->renameDir($userId, $objectPath, $updatedPath, $newName);
        if ($type == 'file') $this->fsRepo->renameFile($userId, $objectPath, $updatedPath, $newName);

        if ($this->diskStorage->renameObject($userId, $newName, $objectPath)) {
            $this->fsRepo->confirmChanges();
            return new OperationResult(true, ['updatedPath' => $updatedPath]);
        } else {
            $this->fsRepo->cancelLastChanges();
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

    public function moveObjects(int $userId, array $items, ?int $toDirId = null): MoveFilesResult
    {
        return $this->moveFiles->execute($userId, $items, $toDirId);
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
