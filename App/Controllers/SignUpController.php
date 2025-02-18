<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;
use App\Controllers\ControllerInterface;

class SignUpController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private UserService $userService) {}

    public function resolve(): void
    {
        $data = $this->request->json();

        $login = trim($data['login']);
        $password = trim($data['password']);

        $registrationResult = $this->userService->registerUser($login, $password);

        if ($registrationResult->success) {
            $this->response->sendJson($registrationResult->data);
        } else $this->response->setStatusCode(400)->sendJson($registrationResult->errors);
    }
}
