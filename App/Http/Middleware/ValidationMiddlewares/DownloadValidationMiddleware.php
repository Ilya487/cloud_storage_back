<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;

class DownloadValidationMiddleware extends ValidationMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        $items = $this->validate(self::ARRAY | self::REQUIRE, 'items', self::GET);

        $key = array_find_key($items, fn($val) => !(filter_var($val, FILTER_VALIDATE_INT) && $val > 0));
        if (!is_null($key)) {
            $this->sendError('items должен состоять из целых неотрицательных чисел');
        }
    }
}
