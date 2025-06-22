<?php

namespace App\Authentication;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Tools\Session;

class SessionAuthentication implements AuthenticationInterface
{
    private ?User $user = null;
    private bool $isAuth  = false;
    private const SESSION_LIFETIME = 60 * 15;

    public function __construct(private Session $session, UserRepository $userRepository)
    {
        if ($userId =  $this->session->get('userId')) {
            if ($this->checkSessionLifetime()) return;

            $user = $userRepository->getById($userId);
            if (!is_null($user)) {
                $this->user = $user;
                $this->isAuth = true;
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
    }

    public function signIn(User $user): bool
    {
        if ($this->isAuth) return false;

        $this->session->set('userId', $user->getId());
        $this->session->set('loginTimeStamp', time());
        $this->isAuth = true;
        $this->user = $user;

        return true;
    }

    private function checkSessionLifetime(): bool
    {
        $createdAt = $this->session->get('loginTimeStamp');
        if (time() - $createdAt > self::SESSION_LIFETIME) {
            $this->logOut();
            return true;
        }
        return false;
    }
}
