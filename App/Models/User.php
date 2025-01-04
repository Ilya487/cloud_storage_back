<?php

namespace App\Models;

use Exception;

class User
{
    private string $id;
    private string $login;
    private string $password;

    public function __construct(string $id, string $login, string $password)
    {
        $this->id = $id;
        if (!$login || !$password) throw new Exception('Некорректные данные пользователя');
        $this->login = $login;
        $this->password = $password;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
