<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;

class UploadValidationMiddleware extends ValidationMiddleware implements MiddlewareInterface
{
    public function handle() {}

    public function initUpload()
    {
        $this->validate(self::REQUIRE | self::STRING, 'fileName', self::JSON);
        $this->validate(self::REQUIRE | self::STRING, 'fileType', self::JSON);
        $this->validate(self::REQUIRE | self::INT, 'fileSize', self::JSON);
        $this->validate(self::REQUIRE | self::INT_OR_EMPTY, 'destinationDirId', self::JSON);
    }
}
