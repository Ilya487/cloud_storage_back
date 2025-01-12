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
        $login = $this->request->post('login');
        $password = $this->request->post('password');

        $authResult = $this->userService->authUser($login, $password);

        if ($authResult->success) {
            $this->response->sendJson(['code' => 200, 'userId' => $authResult->userId]);
        } else $this->response->setStatusCode(400)->sendJson(['code' => 400, ...$authResult->errors]);
    }
}
