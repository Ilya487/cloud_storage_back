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
        $dirName = trim($dirName);
        try {
            $parentPath = $parentDirId ? $this->fsRepo->getDirPath($parentDirId) : '';
            if ($this->diskStorage->createDir($userId, $dirName, $parentPath)) {
                $path = "$parentPath/$dirName";
                $dirId = $this->fsRepo->createDir($userId, $dirName, $path, $parentDirId);
                return new OperationResult(true, ['dirId' => $dirId]);
            } else return new OperationResult(false, null, ['message' => 'Папка с таким именем уже существует']);
        } catch (PDOException) {
            return null;
        }
    }

    public function initializeUserStorage(string $userId): bool
    {
        return $this->diskStorage->initializeUserFolder($userId);
    }
}
