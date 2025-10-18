<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;
use App\Validators\FileSystemNameValidator;

class UploadValidationMiddleware extends ValidationMiddleware implements MiddlewareInterface
{
    public function handle() {}

    public function initUpload()
    {
        $fileName = $this->validate(self::REQUIRE | self::STRING, 'fileName', self::JSON);
        $this->validate(self::REQUIRE | self::INT, 'fileSize', self::JSON);
        $this->validate(self::INT, 'destinationDirId', self::JSON);

        $nameValidationResult = (new FileSystemNameValidator($fileName))->validate();
        if (!$nameValidationResult->success) {
            $this->sendError($nameValidationResult->errors);
        }
    }

    public function uploadChunk()
    {
        $this->validate(self::REQUIRE | self::INT, 'X-Session-Id', self::HEADER);
        $this->validate(self::REQUIRE | self::INT, 'X-Chunk-Num', self::HEADER);
    }

    public function cancelUpload()
    {
        $this->validate(self::REQUIRE | self::INT, 'sessionId', self::GET);
    }

    public function finalize()
    {
        $this->validate(self::REQUIRE | self::INT, 'sessionId', self::JSON);
    }
}
