<?php

namespace App\Controllers;

use App\Authentication\AuthenticationInterface;
use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\UserService;

class AuthController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private AuthenticationInterface $authService, private UserService $userService) {}

    public function resolve(): void
    {
        if ($this->authService->auth()) {
            $this->response->sendJson(['authenticated' => true, 'userId' => $this->authService->getAuthUser()->getId()]);
        } else $this->response->sendJson(['authenticated' => false]);
    }

    public function signup()
    {
        $data = $this->request->json();

        $login = trim($data['login']);
        $password = trim($data['password']);

        $registrationResult = $this->userService->registerUser($login, $password);

        if ($registrationResult->success) {
            $this->response->sendJson($registrationResult->data);
        } else $this->response->setStatusCode(400)->sendJson($registrationResult->errors);
    }

    public function signin()
    {
        $data = $this->request->json();

        $login = trim($data['login']);
        $password = trim($data['password']);

        $authResult = $this->userService->authUser($login, $password);

        if ($authResult->success) {
            $this->response->sendJson($authResult->data);
        } else $this->response->setStatusCode(400)->sendJson($authResult->errors);
    }

    public function logout()
    {
        $this->authService->logOut();
        $this->response->sendJson(['message' => 'Успешный выход из системы']);
    }
}
