<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;
use App\Services\AuthManager;

class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthManager $authManager) {}

    public function handle(Request $request, Response $response)
    {
        if ($this->authManager->auth()) {
            $response->setStatusCode(400)->sendJson(['code' => 400, 'message' => 'Вы уже авторизованы']);
        }
    }
}
