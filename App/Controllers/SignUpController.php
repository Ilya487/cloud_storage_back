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
        $login = trim($this->request->post('login'));
        $password = trim($this->request->post('password'));
        $validationResult = (new SignUpValidator($login, $password))->validate();

        if (count($validationResult) !== 0) {
            $this->response->setStatusCode(400)->sendJson(['errors' => $validationResult]);
        }

        $registrationResult = $this->userService->registerUser($login, $password);
        if (is_null($registrationResult)) $this->response->setStatusCode(500)->sendJson(['message' => 'An unexpected error occurred. Please try again later.']);

        if ($registrationResult->success) {
            $this->response->sendJson($registrationResult->data);
        } else $this->response->setStatusCode(400)->sendJson($registrationResult->errors);
    }
}
