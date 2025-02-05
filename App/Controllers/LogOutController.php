<?php

namespace App\Controllers;

use App\Authentication\AuthenticationInterface;
use App\Http\Response;
use App\Controllers\ControllerInterface;

class LogOutController implements ControllerInterface
{
    public function __construct(private Response $response, private AuthenticationInterface $authService) {}

    public function resolve(): void
    {
        $this->authService->logOut();
        $this->response->sendJson(['message' => 'Успешный выход из системы']);
    }
}
