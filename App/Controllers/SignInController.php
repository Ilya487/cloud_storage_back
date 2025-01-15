<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;
use App\Controllers\ControllerInterface;

class SignInController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private UserService $userService) {}

    public function resolve(): void
    {
        $login = trim($this->request->post('login'));
        $password = trim($this->request->post('password'));

        $authResult = $this->userService->authUser($login, $password);
        if (is_null($authResult)) $this->response->setStatusCode(500)->sendJson(['message' => 'An unexpected error occurred. Please try again later.']);

        if ($authResult->success) {
            $this->response->sendJson($authResult->data);
        } else $this->response->setStatusCode(400)->sendJson($authResult->errors);
    }
}
