<?php

namespace App\DTO;

class RegistrationResult
{
    public function __construct(public readonly bool $success, public readonly ?string $userId, public readonly array $errors = []) {}
}
