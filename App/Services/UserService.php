<?php

namespace App\Services;

use App\Authentication\AuthenticationInterface;
use App\DTO\OperationResult;
use App\Repositories\UserRepository;
use PDOException;

class UserService
{
    public function __construct(private UserRepository $userRepo, private AuthenticationInterface $authService, private FileSystemService $fsService) {}

    public function registerUser(string $login, string $password): ?OperationResult
    {
        try {
            if ($this->userRepo->isLoginExist($login)) {
                return new OperationResult(false, null, ['message' => 'Такой логин уже существует']);
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $userId =  $this->userRepo->insertNewUser($login, $passwordHash);
            $this->fsService->initializeUserStorage($userId);
            return new OperationResult(true, ['userId' => $userId]);
        } catch (PDOException $error) {
            return null;
        }
    }

    public function authUser(string $login, string $password): ?OperationResult
    {
        try {
            $user = $this->userRepo->getByLogin($login);
            if (!$user || !$user->verifyPass($password)) return new OperationResult(false, null, ['message' => 'Неверный логин или пароль']);

            $this->authService->signIn($user);
            return new OperationResult(true, ['userId' => $user->getId()]);
        } catch (PDOException $error) {
            return null;
        }
    }
}
