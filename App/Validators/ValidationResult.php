<?php

namespace App\Validators;

class ValidationResult
{

    public function __construct(private bool $isSuccess, private ?array $errorList = [])
    {
    }

    public function getResult(): bool
    {
        return $this->isSuccess;
    }

    public function getErrorList(): ?array
    {
        return $this->errorList;
    }
}
