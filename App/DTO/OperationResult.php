<?php

namespace App\DTO;

class OperationResult
{
    public function __construct(public readonly bool $success, public readonly ?array $data = null, public readonly ?array $errors = null) {}
}
