<?php

namespace App\Models;

use Exception;

class User
{
    private string $id;
    private string $login;
    private string $password;
    public readonly int $availableDiskSpace;
    public readonly int $totalDiskSpace;

    public function __construct(string $id, string $login, string $password, int $availableDiskSpace, int $totalDiskSpace)
    {
        $this->id = $id;
        if (!$login || !$password) throw new Exception('Некорректные данные пользователя');
        $this->login = $login;
        $this->password = $password;
        $this->availableDiskSpace = $availableDiskSpace;
        $this->totalDiskSpace = $totalDiskSpace;
    }

    public static function createFromArr(array $data): User
    {
        return new self($data['id'], $data['login'], $data['password'], $data['available_disk_space'], $data['total_disk_space']);
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

    public function verifyPass(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}
