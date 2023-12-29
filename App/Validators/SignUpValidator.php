<?php

namespace App\Validators;

use App\Contracts\Validator;
use App\Validators\ValidateResult;

class SignUpValidator implements Validator
{
    private bool $isSuccess = true;
    private array $errorsList;

    public function __construct(
        private ?string $login,
        private ?string $password
    ) {
    }

    public function validate(): ValidateResult
    {
        $this->login = trim($this->login);
        $this->password = trim($this->password);

        if (!$this->checkEmpty()) {
            return new ValidateResult($this->isSuccess, $this->errorsList);
        }
        if (!$this->checkLogin()) {
            return new ValidateResult($this->isSuccess, $this->errorsList);
        }
        if (!$this->checkPassword()) {
            return new ValidateResult($this->isSuccess, $this->errorsList);
        }
        return new ValidateResult($this->isSuccess);
    }

    private function checkEmpty()
    {
        if (!$this->login | !$this->password) {
            $this->isSuccess = false;
            $this->errorsList[] = 'Одно из полей пустое';

            return false;
        } else return true;
    }

    private function checkPassword()
    {
        $pattern = '/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@#\$\!\.]{8,}$/';

        if (!preg_match($pattern, $this->password)) {
            $this->isSuccess = false;
            $this->errorsList[] = 'Неверный формат пароля';

            return false;
        } else return true;
    }

    private function checkLogin()
    {
        $pattern = '/^[A-Za-z\d@#\$\!\.]{5,}$/';

        if (!preg_match($pattern, $this->login)) {
            $this->isSuccess = false;
            $this->errorsList[] = 'Неверный формат логина';

            return false;
        } else return true;
    }
}
