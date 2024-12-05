<?php

namespace App\Controllers;

use App\Validators\SignUpValidator;

class SignUpController extends \App\Contracts\Controller
{
    public function resolve(): void
    {
        $login = $_POST['login'];
        $password = $_POST['password'];

        $validationResult = (new SignUpValidator($login, $password))->validate();

        if ($validationResult->getResult()) {
            $this->sendAnswer(200, ['status' => 'krutoi!']);
        } else $this->sendAnswer(400, ['errors' => $validationResult->getErrorList()]);
    }
}
