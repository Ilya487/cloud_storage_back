<?php

namespace App\Services;

use App\Authentication\AuthenticationInterface;
use App\DTO\AuthResult;
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
        $login = trim($login);
        $password = trim($password);

        if ($this->userRepo->isLoginExist($login)) {
            return new RegistrationResult(false, null, ['Такой логин уже существует']);
        }

        $validationResult = (new SignUpValidator($login, $password))->validate();

        if (!empty($validationResult)) {
            return new RegistrationResult(false, null, $validationResult);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId =  $this->userRepo->insertNewUser($login, $passwordHash);
        return new RegistrationResult(true, $userId);
    }

    public function authUser(string $login, string $password, AuthenticationInterface $authService)
    {
        $login = trim($login);
        $password = trim($password);

        $user = $this->userRepo->getByLogin($login);
        if (!$user || !$user->verifyPass($password)) return new AuthResult(false, null, ['message' => 'Неверный логин или пароль']);

        if ($authService->signIn($user)) return new AuthResult(true, $user->getId());

        return new AuthResult(false, null, ['message' => 'Вы уже авторизованы']);
    }
}
