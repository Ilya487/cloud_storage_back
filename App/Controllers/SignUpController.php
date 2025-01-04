<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;
use App\Contracts\Controller;
use Exception;

class SignUpController implements Controller
{
    public function resolve(Request $request, Response $response): void
    {
        try {
            $login = $request->post('login');
            $password = $request->post('password');

            $registrationResult = (new UserService)->registerUser($login, $password);

            if ($registrationResult->success) {
                $response->sendJson(['code' => 200, 'userId' => $registrationResult->userId]);
            } else $response->setStatusCode(400)->sendJson(['code' => 400, 'errors' => $registrationResult->errors]);
        } catch (Exception) {
            $response->setStatusCode(500)->sendJson(['code' => 500, 'message' => 'An unexpected error occurred. Please try again later.']);
        }
    }
}
