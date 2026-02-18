<?php

namespace App\RequestValidators;

class DownloadValidator extends RequestValidator
{
    public function downloadFile($id)
    {
        return $this->validate(self::REQUIRE | self::INT, 'fileId', ['fileId' => $id]);
    }

    public function iniArchive()
    {
        $items = $this->validate(self::ARRAY | self::REQUIRE, 'items', self::JSON);
        if (count($items) === 0) $this->sendError('items должен содержать хотя бы один элемент');

        $key = array_find_key($items, fn($val) => !(filter_var($val, FILTER_VALIDATE_INT) && $val > 0));
        if (!is_null($key)) {
            $this->sendError('items должен состоять из целых неотрицательных чисел');
        }

        return $items;
    }

    public function checkArchiveStatus($id)
    {
        return $this->validate(self::REQUIRE | self::INT, 'taskId', ['taskId' => $id]);
    }

    public function downloadArchive($id)
    {
        return $this->validate(self::REQUIRE | self::INT, 'taskId', ['taskId' => $id]);
    }
}
