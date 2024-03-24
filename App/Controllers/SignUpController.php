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
            $this->sendAnswer(400, $this->result);
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
                'errors' => $validationResult->getErrorList()
            ];
            return false;
        }
    }
}
