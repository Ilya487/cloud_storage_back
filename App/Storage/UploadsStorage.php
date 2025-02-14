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
        $path = $this->getFullPath($uploadSessionId) . "/$chunkNum";
        return file_put_contents($path, $data);
    }

    private function getFullPath(int $uploadSessionId)
    {
        return $this->storagePath . "/uploads/$uploadSessionId";
    }
}
