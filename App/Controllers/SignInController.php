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
        $data = $this->request->json();
        if (is_null($data)) {
            $this->response->setStatusCode(400)->sendJson(['message' => 'Неверный JSON']);
        }

        $login = trim($data['login']);
        $password = trim($data['password']);

        $authResult = $this->userService->authUser($login, $password);

        if ($authResult->success) {
            $this->response->sendJson($authResult->data);
        } else $this->response->setStatusCode(400)->sendJson($authResult->errors);
    }
}
