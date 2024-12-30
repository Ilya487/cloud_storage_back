<?php

namespace App\Services;

use App\DTO\RegistrationResult;
use App\Repositories\UserRepository;
use App\Validators\SignUpValidator;

class UserService
{
    private UserRepository $userRepo;

    public function __construct()
    {
        $this->userRepo = new UserRepository;
    }

    public function registerUser(string $login, string $password): RegistrationResult
    {
        if ($this->userRepo->isLoginExist($login)) {
            return new RegistrationResult(false, null, ['Такой логин уже существует']);
        }

        $validationResult = (new SignUpValidator($login, $password))->validate();

        if (!empty($validationResult)) {
            return new RegistrationResult(false, null, $validationResult);
        }

        $userId =  $this->userRepo->insertNewUser($login, $password);
        return new RegistrationResult(true, $userId);
    }
}
