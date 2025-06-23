<?php

namespace App\Authentication;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Tools\Session;

class SessionAuthentication implements AuthenticationInterface
{
    private ?User $user = null;
    private bool $isAuth  = false;
    private const SESSION_LIFETIME = 0;

    public function __construct(private Session $session, UserRepository $userRepository, private RememberMeTokenManager $tokenManager)
    {
        if (($userId =  $this->session->get('userId')) && $this->checkSessionLifetime()) {
            $user = $userRepository->getById($userId);
            if (!is_null($user)) {
                $this->user = $user;
                $this->isAuth = true;
            }
        } else {
            $userId = $this->tokenManager->checkToken();
            if ($userId) {
                $user = $userRepository->getById($userId);
                if (!is_null($user)) $this->signIn($user);
            }
        }
    }

    public function auth(): bool
    {
        return $this->isAuth;
    }

    public function getAuthUser(): ?User
    {
        return $this->user;
    }

    public function logOut(): void
    {
        $this->isAuth = false;
        $this->user = null;

        $this->session->destroy();
        $this->tokenManager->deleteToken();
    }

    public function signIn(User $user): bool
    {
        if ($this->isAuth) return false;

        $this->session->set('userId', $user->getId());
        if (self::SESSION_LIFETIME > 0) {
            $this->session->set('loginTimeStamp', time());
        }
        $this->isAuth = true;
        $this->user = $user;

        $this->tokenManager->generateToken($user->getId());
        return true;
    }

    private function checkSessionLifetime(): bool
    {
        if (!$this->session->isSet('loginTimeStamp')) return true;

        $createdAt = $this->session->get('loginTimeStamp');
        if (time() - $createdAt > self::SESSION_LIFETIME) {
            $this->isAuth = false;
            $this->user = null;
            $this->session->destroy();

            return false;
        }
        return true;
    }
}
