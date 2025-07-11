<?php

namespace App\Http\Middleware;

use App\Http\Response;
use App\Services\AuthManager;

class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private Response $response, private AuthManager $authManager) {}

    public function handle()
    {
        if ($this->authManager->auth()) {
            $this->response->setStatusCode(400)->sendJson(['code' => 400, 'message' => 'Вы уже авторизованы']);
        }
    }
}
