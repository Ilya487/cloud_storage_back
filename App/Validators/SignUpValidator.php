<?php

namespace App\Validators;

use App\Contracts\Validator;
use App\Validators\ValidationResult;

class SignUpValidator implements Validator
{
    private const LOGIN_PATTERN = '/^[A-Za-z\d@#\$\!\.]{3,30}$/';
    private const PASSWORD_PATTERN = '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@#\$!\.]{8,30}$/';

    private bool $isSuccess = true;
    private array $errorsList;

    public function __construct(
        private ?string $login,
        private ?string $password
    ) {}

    public function validate(): ValidationResult
    {
        $this->login = trim($this->login);
        $this->password = trim($this->password);

        if (!$this->checkEmpty()) {
            return new ValidationResult($this->isSuccess, $this->errorsList);
        }

        $this->checkLogin();
        $this->checkPassword();

        if ($this->isSuccess) {
            return new ValidationResult($this->isSuccess);
        } else return new ValidationResult($this->isSuccess, $this->errorsList);
    }

    private function checkEmpty()
    {
        if (!$this->login | !$this->password) {
            $this->isSuccess = $this->isSuccess && false;
            $this->errorsList[] = 'Одно из полей пустое';

            return false;
        } else return true;
    }

    private function checkPassword()
    {
        if (!preg_match(self::PASSWORD_PATTERN, $this->password)) {
            $this->isSuccess = $this->isSuccess && false;
            $this->errorsList[] = 'Неверный формат пароля';

            return false;
        } else return true;
    }

    private function checkLogin()
    {
        if (!preg_match(self::LOGIN_PATTERN, $this->login)) {
            $this->isSuccess = $this->isSuccess && false;
            $this->errorsList[] = 'Неверный формат логина';

            return false;
        } else return true;
    }
}
