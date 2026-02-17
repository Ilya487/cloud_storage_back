<?php

namespace App\RequestValidators;

use App\RequestValidators\RequestValidator;
use App\Validators\FileSystemNameValidator;

class UploadValidator extends RequestValidator
{
    public function initUpload()
    {
        $destinationDirId = $this->request->json()['destinationDirId'];
        if ($destinationDirId == 'root') $destinationDirId = null;
        else  $destinationDirId = $this->validate(self::INT | self::REQUIRE, 'destinationDirId', self::JSON);


        $fileName = $this->validate(self::REQUIRE | self::STRING, 'fileName', self::JSON);
        $fileSize = $this->validate(self::REQUIRE | self::INT, 'fileSize', self::JSON);

        $nameValidationResult = (new FileSystemNameValidator($fileName))->validate();
        if (!$nameValidationResult->success) {
            $this->sendError($nameValidationResult->errors);
        }

        return ['destinationDirId' => $destinationDirId, 'fileName' => $fileName, 'fileSize' => $fileSize];
    }

    public function uploadChunk($sessionId)
    {
        $sessionId = $this->validate(self::REQUIRE | self::INT, 'sessionId', ['sessionId' => $sessionId]);
        $chunkNum = $this->validate(self::REQUIRE | self::INT, 'X-Chunk-Num', self::HEADER);

        return ['sessionId' => $sessionId, 'chunkNum' => $chunkNum];
    }

    public function cancelUpload($sessionId)
    {
        return $this->validate(self::REQUIRE | self::INT, 'sessionId', ['sessionId' => $sessionId]);
    }

    public function startBuild($sessionId)
    {
        return $this->validate(self::REQUIRE | self::INT, 'sessionId', ['sessionId' => $sessionId]);
    }

    public function checkStatus($sessionId)
    {
        return $this->validate(self::REQUIRE | self::INT, 'sessionId', ['sessionId' => $sessionId]);
    }
}
