<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

interface ControllerInterface
{
    public function resolve(Request $request, Response $response): void;
}
