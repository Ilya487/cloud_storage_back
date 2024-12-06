<?php

namespace App\Controllers;

use App\Validators\SignUpValidator;
use App\Models\UserModel;
use Exception;

class SignUpController extends \App\Contracts\Controller
{
    public function resolve(): void
    {
        try {
            $login = $_POST['login'];
            $password = $_POST['password'];

            $validationResult = (new SignUpValidator($login, $password))->validate();

            if ($validationResult->getResult()) {
                $user = new UserModel($login, $password);
                $user->save();
                $this->sendAnswer(200, ['status' => 'krutoi!']);
            } else $this->sendAnswer(400, ['errors' => $validationResult->getErrorList()]);
        } catch (Exception $error) {
            $this->sendAnswer(500, ['message' => $error->getMessage()]);
        }
    }
}
