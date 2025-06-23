<?php

namespace App\Authentication;

use App\Models\RememberMeToken;
use App\Repositories\RememberMeTokenRepository;
use DateTime;

class RememberMeTokenManager
{
    private const TOKEN_LIFETIME = 3600 * 24 * 10;

    public function __construct(private RememberMeTokenRepository $tokenRepo) {}

    public function checkToken(): int|false
    {
        if (empty($_COOKIE['remember'])) return false;

        [$selector, $validator] = explode(':', $_COOKIE['remember']);
        $token = $this->tokenRepo->getBySelector($selector);

        if (!$token) return false;
        if ($token->isExpired()) return false;
        if (!$token->verifyValidator($validator)) return false;

        $this->tokenRepo->deleteBySelector($selector);
        return $token->userId;
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
                'secure' => true,
                'samesite' => 'None',
                'httponly' => true,
                'path' => '/'
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
            time() - 3600
        );

        return true;
    }
}
