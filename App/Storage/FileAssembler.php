<?php

namespace App\Storage;

use App\Models\UploadSession;
use App\Storage\UploadsStorage;

class FileAssembler
{
    public function __construct(private UploadsStorage $uploadsStorage) {}

    public function buildFile(UploadSession $uploadSession, string $outputPath): bool
    {
        $stream = fopen($outputPath, 'w');
        if ($stream === false) return false;

        for ($i = 1; $i <= $uploadSession->totalChunksCount; $i++) {
            $data = $this->uploadsStorage->getChunkData($uploadSession->id, $i);
            $res = fwrite($stream, $data);
            if ($res === false) {
                fclose($stream);
                unlink($outputPath);
                return false;
            }
        }
        fclose($stream);

        $size = filesize($outputPath);
        if (!$size) {
            unlink($outputPath);
            return false;
        }

        return true;
    }
}
