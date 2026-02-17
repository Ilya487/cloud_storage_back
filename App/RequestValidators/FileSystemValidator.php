<?php

namespace App\RequestValidators;

use App\Validators\FileSystemNameValidator;

class FileSystemValidator extends RequestValidator
{
    public function create()
    {
        $dirName = $this->validate(self::REQUIRE | self::STRING, 'dirName', self::JSON);
        $parentDirId = $this->validate(self::INT, 'parentDirId', self::JSON);

        $nameValidationResult = (new FileSystemNameValidator($dirName))->validate();
        if (!$nameValidationResult->success) {
            $this->sendError($nameValidationResult->errors);
        }

        return ['dirName' => $dirName, 'parentDirId' => $parentDirId];
    }

    public function getContent(string $dirId)
    {
        if ($dirId == 'root') $res = null;
        else $res = $this->validate(self::INT, 'dirId', ['dirId' => $dirId]);


        return $res;
    }

    public function rename($id)
    {
        $objectId = $this->validate(self::INT, 'objectId', ['objectId' => $id]);
        $updatedName = $this->validate(self::REQUIRE | self::STRING, 'newName', self::JSON);

        $nameValidationResult = (new FileSystemNameValidator($updatedName))->validate();
        if (!$nameValidationResult->success) {
            $this->sendError($nameValidationResult->errors);
        }

        return ['objectId' => $objectId, 'newName' => $updatedName];
    }

    public function delete()
    {
        $items = $this->validate(self::REQUIRE | self::ARRAY, 'items', self::JSON);
        $key = array_find_key($items, fn($val) => !(filter_var($val, FILTER_VALIDATE_INT) && $val > 0));
        if (!is_null($key)) {
            $this->sendError('items должен состоять из целых неотрицательных чисел');
        }

        return $items;
    }

    public function moveItems()
    {
        $toDirId = $this->request->json()['toDirId'];
        if ($toDirId == 'root') $toDirId = null;
        else $toDirId = $this->validate(self::INT | self::REQUIRE, 'toDirId', self::JSON);

        $items = $this->validate(self::REQUIRE | self::ARRAY, 'items', self::JSON);
        $key = array_find_key($items, fn($val) => !(filter_var($val, FILTER_VALIDATE_INT) && $val > 0));
        if (!is_null($key)) {
            $this->sendError('items должен состоять из целых неотрицательных чисел');
        }

        return ['items' => $items, 'toDirId' => $toDirId];
    }

    public function getFolderIdByPath()
    {
        $path =  $this->validate(self::REQUIRE | self::STRING, 'path', self::GET);
        return $path;
    }

    public function search()
    {
        $minQueryLen = 2;
        $query = $this->validate(self::REQUIRE | self::STRING, 'query', self::GET);
        if (mb_strlen($query) < $minQueryLen) $this->sendError("Минимальная длина query $minQueryLen символа");

        return $query;
    }

    public function getFileContent($fileId)
    {
        return $this->validate(self::INT | self::REQUIRE, 'fileId', ['fileId' => $fileId]);
    }
}
