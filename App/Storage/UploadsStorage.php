<?php

namespace App\Storage;

use App\Storage\BaseStorage;

class UploadsStorage extends BaseStorage
{
    public function initializeUploadDir($uploadSessionId): bool
    {
        return mkdir($this->getFullPath($uploadSessionId));
    }

    private function getFullPath(int $uploadSessionId)
    {
        return $this->storagePath . "/uploads/$uploadSessionId";
    }
}
