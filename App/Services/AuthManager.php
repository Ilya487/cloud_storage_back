<?php

namespace App\Services;

use App\Authentication\AuthenticationInterface;
use App\Authentication\RememberMeTokenManager;
use App\DTO\OperationResult;
use App\Models\User;
use App\Repositories\UserRepository;

class AuthManager
{
    public function __construct(
        private UserRepository $userRepo,
        private AuthenticationInterface $authenticator,
        private FileSystemService $fsService,
        private RememberMeTokenManager $tokenManager
    ) {}

    public function registerUser(string $login, string $password): OperationResult
    {
        if ($this->userRepo->isLoginExist($login)) {
            return new OperationResult(false, null, ['message' => 'Такой логин уже существует']);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId =  $this->userRepo->insertNewUser($login, $passwordHash);
        $this->fsService->initializeUserStorage($userId);
        return new OperationResult(true, ['userId' => $userId]);
    }

    public function signinUser(string $login, string $password): OperationResult
    {
        $user = $this->userRepo->getByLogin($login);
        if (!$user || !$user->verifyPass($password)) return new OperationResult(false, null, ['message' => 'Неверный логин или пароль']);

        $this->authenticator->signIn($user);
        $this->tokenManager->generateToken($user->getId());
        return new OperationResult(true, ['userId' => $user->getId()]);
    }

    public function logoutUser()
    {
        $this->authenticator->logOut();
        $this->tokenManager->deleteToken();
    }

    public function refreshUserToken(): OperationResult
    {
        $user = $this->tokenManager->getUserFromToken();
        if ($user === false) return new OperationResult(false, errors: ['message' => 'Не удалось пересоздать токен']);
        $this->authenticator->signIn($user);
        return new OperationResult(true, ['auth' => true, 'login' => $user->getLogin()]);
    }

    public function auth(): bool
    {
        return $this->authenticator->auth();
    }

    public function getAuthUser(): ?User
    {
        return $this->authenticator->getAuthUser();
    }
}
