<?php

namespace App\Http\Middleware;

use App\Authentication\AuthenticationInterface;
use App\Http\Response;

class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(private Response $response, private AuthenticationInterface $authService) {}

    public function handle()
    {
        if ($this->authService->auth()) {
            $this->response->setStatusCode(400)->sendJson(['code' => 400, 'message' => 'Вы уже авторизованы']);
        }
    }
}
