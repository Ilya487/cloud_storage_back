<?php

namespace App\Contracts;

use App\Http\Request;
use App\Http\Response;

interface Controller
{
    public function resolve(Request $request, Response $response): void;
}
