<?php

namespace App\Services;

use App\Authentication\AuthenticationInterface;
use App\DTO\OperationResult;
use App\Repositories\UserRepository;
use App\Validators\SignUpValidator;
use PDOException;

class UserService
{
    public function __construct(private UserRepository $userRepo, private AuthenticationInterface $authService, private FileSystemService $fsService) {}

    public function registerUser(string $login, string $password): ?OperationResult
    {
        try {
            $login = trim($login);
            $password = trim($password);

            if ($this->userRepo->isLoginExist($login)) {
                return new OperationResult(false, null, ['message' => 'Такой логин уже существует']);
            }

            $validationResult = (new SignUpValidator($login, $password))->validate();

            if (!empty($validationResult)) {
                return new OperationResult(false, null, ['errors' => $validationResult]);
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
            $login = trim($login);
            $password = trim($password);

            $user = $this->userRepo->getByLogin($login);
            if (!$user || !$user->verifyPass($password)) return new OperationResult(false, null, ['message' => 'Неверный логин или пароль']);

            $this->authService->signIn($user);
            return new OperationResult(true, ['userId' => $user->getId()]);
        } catch (PDOException $error) {
            return null;
        }
    }
}
