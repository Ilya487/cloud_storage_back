<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

class OptionsRequestMiddleware implements MiddlewareInterface
{
    public function __construct(private Request $request, private Response $response) {}

    public function handle()
    {
        if ($this->request->method == 'OPTIONS') {
            $this->response->setStatusCode(204)->send();
        }
    }
}
