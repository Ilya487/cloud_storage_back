<?php

namespace App\Storage;

use Exception;

class BaseStorage
{
    protected string $storagePath;

    public function __construct(string $storagePath)
    {
        if (!is_dir($storagePath)) throw new Exception('Некорретный путь к хранилищу!');
        $storagePath = str_replace('\\', '/', $storagePath);

        $lastChar = $storagePath[-1];
        while ($lastChar == '/') {
            $path = mb_substr($storagePath, 0, mb_strlen($storagePath) - 1);
            $lastChar = $path[-1];
        }

        if (!is_dir("$storagePath/uploads")) {
            mkdir("$storagePath/uploads");
        }

        $this->storagePath = $storagePath;
    }

    protected function normalizePath(string $path, bool $processLastSlash = true): string
    {
        if (strlen($path) == 0) return '/';

        $path = str_replace('\\', '/', $path);
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        if ($processLastSlash && $path[-1] !== '/') {
            $path = $path . '/';
        }
        return $path;
    }
}
