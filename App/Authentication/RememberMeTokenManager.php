<?php

namespace App\Authentication;

use App\Models\RememberMeToken;
use App\Models\User;
use App\Repositories\RememberMeTokenRepository;
use App\Repositories\UserRepository;
use DateTime;

class RememberMeTokenManager
{
    private const TOKEN_LIFETIME = 3600 * 24 * 10;

    public function __construct(private RememberMeTokenRepository $tokenRepo, private UserRepository $userRepo) {}

    public function getUserFromToken(): User|false
    {
        if (empty($_COOKIE['remember'])) return false;

        [$selector, $validator] = explode(':', $_COOKIE['remember']);
        $token = $this->tokenRepo->getBySelector($selector);

        if (!$token) return false;
        if ($token->isExpired()) {
            $this->tokenRepo->deleteBySelector($selector);
            return false;
        }
        if (!$token->verifyValidator($validator)) return false;

        $user = $this->userRepo->getById($token->userId);
        if (is_null($user)) {
            $this->tokenRepo->deleteBySelector($selector);
            return false;
        }

        $this->tokenRepo->deleteBySelector($selector);
        $this->generateToken($user->getId());
        return $user;
    }

    public function generateToken(int $userId): void
    {
        $selector = bin2hex(random_bytes(8));
        $validatorHash = hash('sha256', random_bytes(30));
        $token = "$selector:$validatorHash";

        $this->tokenRepo->saveToken(new RememberMeToken(
            $selector,
            $validatorHash,
            $userId,
            DateTime::createFromTimestamp(time() + self::TOKEN_LIFETIME)
        ));

        setcookie(
            'remember',
            $token,
            [
                'expires' => time() + self::TOKEN_LIFETIME,
                'httponly' => true,
                'path' => '/api/auth'
            ]
        );
    }

    public function deleteToken(): bool
    {
        if (empty($_COOKIE['remember'])) return false;
        $selector = explode(':', $_COOKIE['remember'])[0];

        $this->tokenRepo->deleteBySelector($selector);
        setcookie(
            'remember',
            '',
            1,
            '/auth'
        );

        return true;
    }
}
