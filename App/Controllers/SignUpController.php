<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;
use App\Controllers\ControllerInterface;
use App\Validators\SignUpValidator;

class SignUpController implements ControllerInterface
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
        $validationResult = (new SignUpValidator($login, $password))->validate();

        if (count($validationResult) !== 0) {
            $this->response->setStatusCode(400)->sendJson(['errors' => $validationResult]);
        }

        $registrationResult = $this->userService->registerUser($login, $password);

        if ($registrationResult->success) {
            $this->response->sendJson($registrationResult->data);
        } else $this->response->setStatusCode(400)->sendJson($registrationResult->errors);
    }
}
