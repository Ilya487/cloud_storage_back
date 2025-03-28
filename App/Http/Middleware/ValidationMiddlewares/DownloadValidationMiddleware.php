<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;

class DownloadValidationMiddleware extends ValidationMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        $this->validate(self::REQUIRE | self::INT, 'fileId', self::GET);
    }
}
