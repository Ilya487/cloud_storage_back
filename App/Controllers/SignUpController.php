<?php

namespace App\Controllers;

use App\Contracts\Validator;
use App\Validators\SignUpValidator;

class SignUpController extends \App\Contracts\Controller
{
    private array  $result;

    public function resolve(): void
    {
        $login = $_POST['login'];
        $password = $_POST['password'];

        $validationResult = $this->validateData(new SignUpValidator($login, $password));

        if (!$validationResult) {
            $this->sendAnswer(400);
        }
    }

    private function validateData(Validator $validator): bool
    {
        $validationResult = $validator->validate();

        if ($validationResult->getResult()) {
            return true;
        } else {
            $this->result = [
                'isSuccess' => $validationResult->getResult(),
                'errorList' => $validationResult->getErrorList()
            ];
            return false;
        }
    }

    private function sendAnswer(int $code)
    {
        header(self::CONTENT_TYPE_JSON);
        http_response_code($code);

        $json =  json_encode($this->result);
        echo $json;
    }
}
