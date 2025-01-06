<?php

namespace App\Http\Middleware;

use App\Authentication\AuthenticationInterface;
use App\Http\Request;
use App\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthenticationInterface $authService) {}

    public function handle(Request $request, Response $response)
    {
        if (!$this->authService->auth()) {
            $response->setStatusCode(401)->sendJson(['code' => 401, 'message' => 'Вы должны авторизоваться']);
        }
    }
}
