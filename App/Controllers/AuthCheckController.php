<?php

namespace App\Controllers;

use App\Authentication\AuthenticationInterface;
use App\Http\Response;
use App\Controllers\ControllerInterface;

class AuthCheckController implements ControllerInterface
{
    public function __construct(private Response $response, private AuthenticationInterface $authService) {}

    public function resolve(): void
    {
        if ($this->authService->auth()) {
            $this->response->sendJson(['authenticated' => true, 'userId' => $this->authService->getAuthUser()->getId()]);
        } else $this->response->sendJson(['authenticated' => false]);
    }
}
