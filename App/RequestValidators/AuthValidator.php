<?php

namespace App\RequestValidators;

use App\RequestValidators\RequestValidator;
use App\Validators\SignUpValidator;

class AuthValidator extends RequestValidator
{
    public function signin()
    {
        $login = $this->validate(self::STRING | self::REQUIRE, 'login', self::JSON);
        $password = $this->validate(self::STRING | self::REQUIRE, 'password', self::JSON);
        return ['login' => $login, 'password' => $password];
    }

    public function signup()
    {
        $login = $this->validate(self::STRING | self::REQUIRE, 'login', self::JSON);
        $password = $this->validate(self::STRING | self::REQUIRE, 'password', self::JSON);

        $validationRes = (new SignUpValidator($login, $password))->validate();
        if (!$validationRes->success) {
            $this->sendError($validationRes->errors);
        }
        return ['login' => $login, 'password' => $password];
    }
}
