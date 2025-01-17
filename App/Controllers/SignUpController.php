<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;
use App\Controllers\ControllerInterface;
use Exception;

class SignUpController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private UserService $userService) {}

    public function resolve(): void
    {
        try {
            $login = $this->request->post('login');
            $password = $this->request->post('password');

            $registrationResult = $this->userService->registerUser($login, $password);

            if ($registrationResult->success) {
                $this->response->sendJson(['code' => 200, 'userId' => $registrationResult->userId]);
            } else $this->response->setStatusCode(400)->sendJson(['code' => 400, 'errors' => $registrationResult->errors]);
        } catch (Exception) {
            $this->response->setStatusCode(500)->sendJson(['code' => 500, 'message' => 'An unexpected error occurred. Please try again later.']);
        }
    }
}
