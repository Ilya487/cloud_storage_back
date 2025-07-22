<?php

namespace App\Http\Middleware;

use App\Http\Response;
use App\Services\AuthManager;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private Response $response, private AuthManager $authManager) {}

    public function handle()
    {
        if (!$this->authManager->auth()) {
            $this->response->setStatusCode(401)->sendJson(['code' => 401, 'message' => 'Пользователь не авторизован']);
        }
    }
}
