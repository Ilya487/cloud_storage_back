<?php

namespace App\DTO;

class ValidationResult
{
    public function __construct(public readonly bool $success, public readonly ?array $errors = null) {}
}
