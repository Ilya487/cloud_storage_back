<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;
use App\Validators\FileSystemNameValidator;

class FolderValidationMiddleware extends ValidationMiddleware implements MiddlewareInterface
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

    public function renameFolder()
    {
        $this->validate(self::REQUIRE | self::INT, 'dirId', self::JSON);
        $updatedDirName = $this->validate(self::REQUIRE | self::STRING, 'newName', self::JSON);

        $nameValidationResult = (new FileSystemNameValidator($updatedDirName))->validate();
        if (!$nameValidationResult->success) {
            $this->sendError($nameValidationResult->errors);
        }
    }

    public function deleteFolder()
    {
        $this->validate(self::REQUIRE | self::INT, 'dirId', self::GET);
    }
}
