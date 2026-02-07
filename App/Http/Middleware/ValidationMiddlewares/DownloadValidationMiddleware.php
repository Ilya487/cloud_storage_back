<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;

class DownloadValidationMiddleware extends ValidationMiddleware implements MiddlewareInterface
{
    public function handle() {}

    public function downloadFile()
    {
        $this->validate(self::REQUIRE | self::INT, 'fileId', self::GET);
    }

    public function iniArchive()
    {
        $items = $this->validate(self::ARRAY | self::REQUIRE, 'items', self::JSON);
        if (count($items) === 0) $this->sendError('items должен содержать хотя бы один элемент');

        $key = array_find_key($items, fn($val) => !(filter_var($val, FILTER_VALIDATE_INT) && $val > 0));
        if (!is_null($key)) {
            $this->sendError('items должен состоять из целых неотрицательных чисел');
        }
    }

    public function checkArchiveStatus()
    {
        $this->validate(self::REQUIRE | self::INT, 'taskId', self::GET);
    }

    public function downloadArchive()
    {
        $this->validate(self::REQUIRE | self::INT, 'taskId', self::GET);
    }
}
