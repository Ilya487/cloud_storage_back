<?php

namespace App\Models;

use App\Models\BaseModel;

class UserModel extends BaseModel
{
    public function __construct(private string $login, private string $password)
    {
        parent::__construct();
    }

    protected  function getTableName(): string
    {
        return 'users';
    }

    protected  function getFieldsNames(): array
    {
        return ['login', 'password'];
    }

    protected function getFieldsWithValues(): array
    {
        return ['login' => $this->login, 'password' => password_hash($this->password, PASSWORD_DEFAULT)];
    }

    public function getLogin(): string
    {
        return $this->login;
    }
    public function setLogin(string $newLogin)
    {
        $this->login = $newLogin;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(string $newPassword)
    {
        $this->password = $newPassword;
    }
}
