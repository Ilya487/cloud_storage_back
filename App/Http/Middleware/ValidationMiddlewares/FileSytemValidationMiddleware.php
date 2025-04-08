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
        $this->validate(self::REQUIRE | self::INT_OR_EMPTY, 'parentDirId', self::JSON);

        $nameValidationResult = (new FileSystemNameValidator($dirName))->validate();
        if (!$nameValidationResult->success) {
            $this->sendError($nameValidationResult->errors);
        }
    }

    public function getContent()
    {
        $this->validate(self::REQUIRE | self::INT_OR_EMPTY, 'dirId', self::GET);
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
        $this->validate(self::REQUIRE | self::INT, 'objectId', self::GET);
    }

    public function moveItem()
    {
        $this->validate(self::REQUIRE | self::INT, 'itemId', self::JSON);
        $this->validate(self::REQUIRE | self::INT_OR_EMPTY, 'toDirId', self::JSON);
    }
}
