<?php

namespace App\Validators;

class SignUpValidator
{
    private const LOGIN_PATTERN = '/^[A-Za-z\d@#\$\!\.]{3,30}$/';
    private const PASSWORD_PATTERN = '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@#\$!\.]{8,30}$/';

    private array $errorsList = [];
    private ?string $login;
    private ?string $password;

    public function __construct(?string $login, ?string $password)
    {
        $this->login = trim($login);
        $this->password = trim($password);
    }

    /**
     * @return array error list
     */
    public function validate(): array
    {
        if (!$this->checkEmpty()) {
            return $this->errorsList;
        }

        $this->checkLogin();
        $this->checkPassword();

        return $this->errorsList;
    }

    private function checkEmpty()
    {
        if (!$this->login | !$this->password) {
            $this->errorsList[] = 'Одно из полей пустое';

            return false;
        } else return true;
    }

    private function checkPassword()
    {
        if (!preg_match(self::PASSWORD_PATTERN, $this->password)) {
            $this->errorsList[] = 'Неверный формат пароля';

            return false;
        } else return true;
    }

    private function checkLogin()
    {
        if (!preg_match(self::LOGIN_PATTERN, $this->login)) {
            $this->errorsList[] = 'Неверный формат логина';

            return false;
        } else return true;
    }
}
