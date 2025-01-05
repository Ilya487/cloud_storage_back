<?php

namespace App\Tools;

class Session
{
    private array $sessionCookieParams = [
        'secure' => true,
        'samesite' => 'None',
        'httponly' => true
    ];

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params($this->sessionCookieParams);
            session_start();
        }
    }

    public function set(string $key, mixed $value)
    {
        $_SESSION[$key] = $value;
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?: null;
    }

    public function delete(string $key)
    {
        unset($_SESSION[$key]);
    }

    public function clear()
    {
        $_SESSION = [];
    }

    public function destroy()
    {
        $this->clear();
        session_destroy();
        setcookie(
            session_name(),
            '',
            [...$this->sessionCookieParams, 'expires' => time() - 1]
        );
    }
}
