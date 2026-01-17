<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Models\FsObjectType;
use App\Repositories\FileSystemRepository;
use App\Storage\DiskStorage;
use App\UseCases\DeleteFilesUseCase;
use App\UseCases\MoveFilesUseCase;

class FileSystemService
{
    public function __construct(
        private DiskStorage $diskStorage,
        private FileSystemRepository $fsRepo,
        private MoveFilesUseCase $moveFiles,
        private DeleteFilesUseCase $deleteFiles,
    ) {}

    public function createFolder(int $userId, string $dirName, ?int $parentDirId = null): OperationResult
    {
        $parentPath = $parentDirId ? $this->fsRepo->getPathById($parentDirId, $userId) : '';
        if ($parentPath === false) return  OperationResult::createError(['message' => 'Неверный айди родительского каталога']);

        $path = "$parentPath/$dirName";
        $dirId = $this->fsRepo->createDir($userId, $dirName, $path, $parentDirId);
        if ($this->diskStorage->createDir($userId, $dirName, $parentPath)) {
            $this->fsRepo->confirmChanges();
            return OperationResult::createSuccess(['dirId' => $dirId]);
        } else {
            $this->fsRepo->cancelLastChanges();
            return  OperationResult::createError(['message' => 'Папка с таким именем уже существует']);
        }
    }

    public function getFolderContent(int $userId, ?int $dirId = null): OperationResult
    {
        if (!is_null($dirId) && $this->fsRepo->getTypeById($userId, $dirId) == 'file')
            return OperationResult::createError(['message' => 'Указан неверный айди']);

        $pathToSelectedDir = is_null($dirId) ? '/' : $this->fsRepo->getPathById($dirId, $userId);
        if ($pathToSelectedDir === false) return OperationResult::createError(['message' => 'Указан неверный айди']);

        $catalogData = $this->fsRepo->getDirContent($userId, $dirId);

        if ($catalogData !== false) return OperationResult::createSuccess(['path' => $pathToSelectedDir, 'contents' => $catalogData]);
        else return OperationResult::createError(['message' => 'Указан неверный айди']);
    }

    public function initializeUserStorage(int $userId): bool
    {
        return $this->diskStorage->initializeUserFolder($userId);
    }

    public function renameObject(int $userId, int $objectId, string $newName): OperationResult
    {
        $fsObject = $this->fsRepo->getById($userId, $objectId);
        if ($fsObject === false) return OperationResult::createError(['message' => 'Указан неверный айди']);

        $currentPath = $fsObject->getPath();
        $updatedPath = $fsObject->rename($newName);

        $this->fsRepo->rename($fsObject->ownerId, $fsObject->type, $currentPath, $updatedPath, $fsObject->getName());

        if ($this->diskStorage->renameObject($userId, $newName, $currentPath)) {
            $this->fsRepo->confirmChanges();
            return OperationResult::createSuccess(['updatedPath' => $updatedPath]);
        } else {
            $this->fsRepo->cancelLastChanges();
            return OperationResult::createError([
                'message' => 'Не удалось переименовать ' . ($fsObject->type == FsObjectType::DIR ? 'папку' : 'файл')
            ]);
        }
    }

    public function deleteObjects(int $userId, array $items): OperationResult
    {
        return $this->deleteFiles->execute($userId, $items);
    }

    public function moveObjects(int $userId, array $items, ?int $toDirId = null): OperationResult
    {
        return $this->moveFiles->execute($userId, $items, $toDirId);
    }

    public function getDirIdByPath(int $userId, string $path): OperationResult
    {
        $path = str_replace('\\', '/', $path);

        if ($path == '/') return OperationResult::createSuccess(['dirId' => 'root']);

        $dirId = $this->fsRepo->getDirIdByPath($userId, $path);
        if ($dirId === false) return OperationResult::createError(['message' => 'Папка с данным расположением не найдена']);
        else return OperationResult::createSuccess(['dirId' => $dirId]);
    }
}
