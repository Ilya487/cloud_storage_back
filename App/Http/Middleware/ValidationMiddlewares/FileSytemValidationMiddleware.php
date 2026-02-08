<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;
use App\Validators\FileSystemNameValidator;

class FileSytemValidationMiddleware extends ValidationMiddleware implements MiddlewareInterface
{
    public function handle() {}

    public function create()
    {
        $dirName = $this->validate(self::REQUIRE | self::STRING, 'dirName', self::JSON);
        $this->validate(self::INT, 'parentDirId', self::JSON);

        $nameValidationResult = (new FileSystemNameValidator($dirName))->validate();
        if (!$nameValidationResult->success) {
            $this->sendError($nameValidationResult->errors);
        }
    }

    public function getContent()
    {
        $this->validate(self::INT, 'dirId', self::GET);
    }

    public function rename()
    {
        $this->validate(self::REQUIRE | self::INT, 'objectId', self::JSON);
        $updatedDirName = $this->validate(self::REQUIRE | self::STRING, 'newName', self::JSON);

        $nameValidationResult = (new FileSystemNameValidator($updatedDirName))->validate();
        if (!$nameValidationResult->success) {
            $this->sendError($nameValidationResult->errors);
        }
    }

    public function delete()
    {
        $items = $this->validate(self::REQUIRE | self::ARRAY, 'items', self::JSON);
        $key = array_find_key($items, fn($val) => !(filter_var($val, FILTER_VALIDATE_INT) && $val > 0));
        if (!is_null($key)) {
            $this->sendError('items должен состоять из целых неотрицательных чисел');
        }
    }

    public function moveItems()
    {
        $items = $this->validate(self::REQUIRE | self::ARRAY, 'items', self::JSON);
        $key = array_find_key($items, fn($val) => !(filter_var($val, FILTER_VALIDATE_INT) && $val > 0));
        if (!is_null($key)) {
            $this->sendError('items должен состоять из целых неотрицательных чисел');
        }

        $this->validate(self::INT, 'toDirId', self::JSON);
    }

    public function getFolderIdByPath()
    {
        $this->validate(self::REQUIRE | self::STRING, 'path', self::GET);
    }

    public function search()
    {
        $minQueryLen = 2;
        $query = $this->validate(self::REQUIRE | self::STRING, 'query', self::GET);
        if (mb_strlen($query) < $minQueryLen) $this->sendError("Минимальная длина query $minQueryLen символа");
    }
}
