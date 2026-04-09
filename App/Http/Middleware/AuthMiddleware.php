<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthManager;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthManager $authManager) {}

    public function handle(Request $request, Response $response)
    {
        if (!$this->authManager->auth()) {
            $response->setStatusCode(401)->sendJson(['code' => 401, 'message' => 'Пользователь не авторизован']);
        }
    }
}
