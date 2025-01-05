<?php

namespace App\Authentication;

use App\Models\User;
use App\Repositories\UserRepository;
use App\Tools\Session;

class SessionAuthentication implements AuthenticationInterface
{
    private ?User $user = null;
    private bool $isAuth  = false;
    private Session $session;

    public function __construct()
    {
        $this->session = new Session;

        if ($userId =  $this->session->get('userId')) {
            $user = (new UserRepository)->getById($userId);
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

    public function signOut(): bool
    {
        if ($this->isAuth) {
            $this->isAuth = false;
            $this->user = null;

            $this->session->destroy();

            return true;
        }

        return false;
    }

    public function signIn(User $user): bool
    {
        if ($this->isAuth) return false;

        $this->session->set('userId', $user->getId());
        $this->isAuth = true;
        $this->user = $user;

        return true;
    }
}
