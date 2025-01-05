<?php

namespace App\DTO;

class AuthResult
{
    public function __construct(public readonly bool $success, public readonly ?string $userId, public readonly array $errors = []) {}
}
