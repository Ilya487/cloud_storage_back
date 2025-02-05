<?php

namespace App\Validators;

use App\DTO\ValidationResult;

class SignUpValidator
{
    private const LOGIN_PATTERN = '/^[A-Za-z\d@#\$\!\.]{3,30}$/';
    private const PASSWORD_PATTERN = '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@#\$!\.]{8,30}$/';

    private array $errorList = [];
    private ?string $login;
    private ?string $password;

    public function __construct(?string $login, ?string $password)
    {
        $this->login = trim($login);
        $this->password = trim($password);
    }

    public function validate(): ValidationResult
    {
        if (!$this->checkEmpty()) {
            return new ValidationResult(false, $this->errorList);
        }

        $this->checkLogin();
        $this->checkPassword();

        if (empty($this->errorList)) return new ValidationResult(true);
        else return new ValidationResult(false, $this->errorList);
    }

    private function checkEmpty(): bool
    {
        if (!$this->login | !$this->password) {
            $this->errorList[] = 'Одно из полей пустое';

            return false;
        } else return true;
    }

    private function checkPassword()
    {
        if (!preg_match(self::PASSWORD_PATTERN, $this->password)) {
            $this->errorList[] = 'Неверный формат пароля';

            return false;
        } else return true;
    }

    private function checkLogin()
    {
        if (!preg_match(self::LOGIN_PATTERN, $this->login)) {
            $this->errorList[] = 'Неверный формат логина';

            return false;
        } else return true;
    }
}
