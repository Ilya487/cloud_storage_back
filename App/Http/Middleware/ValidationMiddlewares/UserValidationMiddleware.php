<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;
use App\Validators\SignUpValidator;

class UserValidationMiddleware extends ValidationMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        $login = $this->validate(self::STRING | self::REQUIRE, 'login', self::JSON);
        $password = $this->validate(self::STRING | self::REQUIRE, 'password', self::JSON);

        if ($this->request->endPoint == '/signup') {
            $validationRes = (new SignUpValidator($login, $password))->validate();
            if (!$validationRes->success) {
                $this->sendError($validationRes->errors);
            }
        }
    }
}
