<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Exceptions\NotFoundException;
use App\Models\FileSystemObject;
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

        return OperationResult::createSuccess(['dirId' => $dirId]);
    }

    public function getFolderContent(int $userId, ?int $dirId = null): OperationResult
    {
        $selectedDir = is_null($dirId) ? FileSystemObject::createRootDir($userId) : $this->fsRepo->getById($userId, $dirId);
        if ($selectedDir === false) throw new NotFoundException('Указаная директория не найдена');
        if ($selectedDir->isFile())
            return OperationResult::createError(['message' => 'Выбран файл']);

        $catalogData = $this->fsRepo->getDirContent($userId, $dirId);

        if ($catalogData !== false) return OperationResult::createSuccess(['path' => $selectedDir->getPath(), 'contents' => $catalogData]);
        else return OperationResult::createError(['message' => 'Не удалось получить содержимое папки']);
    }

    public function initializeUserStorage(int $userId): bool
    {
        return $this->diskStorage->initializeUserFolder($userId);
    }

    public function renameObject(int $userId, int $objectId, string $newName): OperationResult
    {
        $fsObject = $this->fsRepo->getById($userId, $objectId);
        if ($fsObject === false) return OperationResult::createError(['message' => 'Указан неверный айди']);

        $this->fsRepo->rename($fsObject, $newName);
        return OperationResult::createSuccess(['updatedPath' => $fsObject->getPath()]);
    }

    public function deleteObjects(int $userId, array $items): OperationResult
    {
        return $this->deleteFiles->softDelete($userId, $items);
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

    public function search(int $userId, string $query): OperationResult
    {
        $searchRes = $this->fsRepo->search($userId, $query);
        return OperationResult::createSuccess([
            'count' => count($searchRes),
            'matches' => $searchRes
        ]);
    }
}
