<?php

namespace App\Contracts;

use App\Validators\ValidateResult;

interface Validator
{
    public function validate(): ValidateResult;
}
