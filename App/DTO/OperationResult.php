<?php

namespace App\DTO;

class OperationResult
{
    private function __construct(public readonly bool $success, public readonly ?array $data = null, public readonly ?array $errors = null) {}

    public static function createSuccess(array $data)
    {
        return new self(true, $data);
    }

    public static function createError(array $errors)
    {
        return new self(false, errors: $errors);
    }
}
