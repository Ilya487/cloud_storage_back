<?php

namespace App\Storage;

use App\Storage\BaseStorage;

class UploadsStorage extends BaseStorage
{
    public function __construct(string $storagePath)
    {
        parent::__construct($storagePath);
        if (!is_dir("$storagePath/uploads")) {
            mkdir("$storagePath/uploads");
        }
    }

    public function initializeUploadDir($uploadSessionId): bool
    {
        return mkdir($this->getFullPath($uploadSessionId));
    }

    /**
     * @param resource $chunkStream
     */
    public function uploadChunk(int $uploadSessionId, int $chunkNum, $chunkStream): bool
    {
        $path = $this->getFullPath($uploadSessionId, $chunkNum);
        if (file_exists($path)) return true;

        $chunkFile = fopen($path, 'w');
        if ($chunkFile === false) return false;

        rewind($chunkStream);
        if (stream_copy_to_stream($chunkStream, $chunkFile) === false) return false;
        fclose($chunkFile);

        return true;
    }

    public function getChunkData(int $uploadSessionId, int $chunkNum): string|false
    {
        $path = $this->getFullPath($uploadSessionId, $chunkNum);
        return file_get_contents($path);
    }

    public function deleteSessionDir(int $uploadSessionId): bool
    {
        $path = $this->getFullPath($uploadSessionId);
        if (!is_dir($path)) return true;
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
