<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Repositories\FileSystemRepository;
use App\Storage\DiskStorage;
use PDOException;

class FileSystemService
{
    public function __construct(private DiskStorage $diskStorage, private FileSystemRepository $fsRepo) {}

    public function createFolder(string $userId, string $dirName, string $parentDirId = null): ?OperationResult
    {
        $parentPath = $parentDirId ? $this->fsRepo->getDirPathById($parentDirId) : '';
        if ($parentPath === false) return new OperationResult(false, null, ['message' => 'Неверный айди родительского каталога']);

        if ($this->diskStorage->createDir($userId, $dirName, $parentPath)) {
            $path = "$parentPath/$dirName";
            $dirId = $this->fsRepo->createDir($userId, $dirName, $path, $parentDirId);
            return new OperationResult(true, ['dirId' => $dirId]);
        } else return new OperationResult(false, null, ['message' => 'Папка с таким именем уже существует']);
    }

    public function getFolderContent(string $userId, ?string $dirId): ?OperationResult
    {
        $catalogData = $this->fsRepo->getDirContent($userId, $dirId);

        if ($catalogData !== false) return new OperationResult(true, ['contents' => $catalogData]);
        else return new OperationResult(false, null, ['message' => 'Неверный айди пользователя или каталога']);
    }

    public function initializeUserStorage(string $userId): bool
    {
        return $this->diskStorage->initializeUserFolder($userId);
    }
}
