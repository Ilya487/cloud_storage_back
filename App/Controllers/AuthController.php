<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\AuthManager;

class AuthController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response,  private AuthManager $authManager) {}

    public function resolve(): void {}

    public function getUser()
    {
        $user = $this->authManager->getAuthUser();
        if (is_null($user)) {
            $this->response->setStatusCode(401)->sendJson(['auth' => false]);
        } else {
            $this->response->sendJson(['auth' => true, 'login' => $user->getLogin()]);
        }
    }

    public function signup()
    {
        $data = $this->request->json();

        $login = trim($data['login']);
        $password = trim($data['password']);

        $registrationResult = $this->authManager->registerUser($login, $password);

        if ($registrationResult->success) {
            $this->response->sendJson($registrationResult->data);
        } else $this->response->setStatusCode(400)->sendJson($registrationResult->errors);
    }

    public function signin()
    {
        $data = $this->request->json();

        $login = trim($data['login']);
        $password = trim($data['password']);

        $authResult = $this->authManager->signinUser($login, $password);

        if ($authResult->success) {
            $this->response->sendJson($authResult->data);
        } else $this->response->setStatusCode(400)->sendJson($authResult->errors);
    }

    public function logout()
    {
        $this->authManager->logoutUser();
        $this->response->sendJson(['message' => 'Успешный выход из системы']);
    }

    public function refresh()
    {
        $result = $this->authManager->refreshUserToken();
        if ($result->success) {
            $this->response->sendJson($result->data);
        } else {
            $this->response->setStatusCode(401)->sendJson($result->errors);
        }
    }
}
