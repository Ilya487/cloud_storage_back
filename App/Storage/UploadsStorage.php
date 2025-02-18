<?php

namespace App\Storage;

use App\Storage\BaseStorage;

class UploadsStorage extends BaseStorage
{
    public function initializeUploadDir($uploadSessionId): bool
    {
        return mkdir($this->getFullPath($uploadSessionId));
    }

    public function uploadChunk(int $uploadSessionId, int $chunkNum, string $data): bool
    {
        $path = $this->getFullPath($uploadSessionId, $chunkNum);
        return file_put_contents($path, $data);
    }

    public function getChunkData(int $uploadSessionId, int $chunkNum): string|false
    {
        $path = $this->getFullPath($uploadSessionId, $chunkNum);
        return file_get_contents($path);
    }

    public function deleteSessionDir(int $uploadSessionId)
    {
        $path = $this->getFullPath($uploadSessionId);
        return $this->deleteDirectoryRecursively($path);
    }

    private function getFullPath(int $uploadSessionId, ?int $chunkNum = null): string
    {
        if ($chunkNum) {
            return $this->storagePath . "/uploads/$uploadSessionId/$chunkNum";
        }
        return $this->storagePath . "/uploads/$uploadSessionId";
    }
}
