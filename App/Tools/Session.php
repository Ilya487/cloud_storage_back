<?php

namespace App\Tools;

class Session
{
    private array $sessionCookieParams = [
        'httponly' => true,
        'path' => '/api',
    ];

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params($this->sessionCookieParams);
            session_start();
            session_write_close();
        }
    }

    public function set(string $key, mixed $value)
    {
        session_start();
        $_SESSION[$key] = $value;
        session_write_close();
    }

    public function isSet(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function get(string $key): mixed
    {
        return $_SESSION[$key] ?: null;
    }

    public function delete(string $key)
    {
        session_start();
        unset($_SESSION[$key]);
        session_write_close();
    }

    public function clear()
    {
        session_start();
        session_unset();
        session_write_close();
    }

    public function destroy()
    {
        session_start();
        session_unset();
        session_destroy();
        setcookie(session_name(), '', time() - 3600);
        session_write_close();
    }
}
