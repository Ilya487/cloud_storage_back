<?php

namespace App\Http\Middleware;

use App\Authentication\AuthenticationInterface;
use App\Http\Request;
use App\Http\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Response $response, private AuthenticationInterface $authService) {}

    public function handle()
    {
        if (!$this->authService->auth()) {
            $this->response->setStatusCode(401)->sendJson(['code' => 401, 'message' => 'Пользователь не авторизован']);
        }
    }
}
