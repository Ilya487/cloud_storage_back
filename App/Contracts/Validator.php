<?php

namespace App\Contracts;

use App\Validators\ValidationResult;

interface Validator
{
    public function validate(): ValidationResult;
}
