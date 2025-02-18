<?php

namespace App\Storage;

use App\DTO\FileBuildResult;
use App\Models\UploadSession;
use App\Repositories\FileSystemRepository;
use App\Storage\UploadsStorage;
use App\Storage\DiskStorage;

class FileAssembler
{
    public function __construct(private UploadsStorage $uploadsStorage, private DiskStorage $diskStorage, private FileSystemRepository $fsRepo) {}

    public function buildFile(UploadSession $uploadSession): FileBuildResult
    {
        [$distanationDirPath, $filePath] = $this->getPath(
            $uploadSession->userId,
            $uploadSession->fileName,
            $uploadSession->destinationDirId
        );

        for ($i = 1; $i <= $uploadSession->totalChunksCount; $i++) {
            $data = $this->uploadsStorage->getChunkData($uploadSession->id, $i);
            $res = $this->diskStorage->putContentInFile(
                $uploadSession->userId,
                $distanationDirPath,
                $uploadSession->fileName,
                $data
            );
            if ($res === false)  return new FileBuildResult(false);
        }
        $this->uploadsStorage->deleteSessionDir($uploadSession->id);

        $size = $this->diskStorage->getFileSize($uploadSession->userId, $filePath);
        if (!$size) return new FileBuildResult(false);
        return new FileBuildResult(true, $filePath, $size);
    }

    private function getPath(int $userId, string $fileName, ?int $dirId): array
    {
        $distanationDirPath = $dirId == false ? '/' : $this->fsRepo->getPathById($dirId, $userId);
        $filePath = $distanationDirPath == '/' ? "$distanationDirPath" . $fileName : "$distanationDirPath/" . $fileName;

        return [$distanationDirPath, $filePath];
    }
}
