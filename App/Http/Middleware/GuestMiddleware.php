<?php

namespace App\Http\Middleware;

use App\Authentication\AuthenticationInterface;
use App\Http\Request;
use App\Http\Response;

class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthenticationInterface $authService) {}

    public function handle(Request $request, Response $response)
    {
        if ($this->authService->auth()) {
            $response->setStatusCode(400)->sendJson(['code' => 400, 'message' => 'Вы уже авторизованы']);
        }
    }
}
