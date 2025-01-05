<?php

namespace App\Controllers;

use App\Authentication\SessionAuthentication;
use App\Http\Request;
use App\Http\Response;
use App\Services\UserService;
use App\Contracts\Controller;

class SignInController implements Controller
{
    public function resolve(Request $request, Response $response): void
    {
        $login = $request->post('login');
        $password = $request->post('password');

        $authResult = (new UserService)->authUser($login, $password, new SessionAuthentication);

        if ($authResult->success) {
            $response->sendJson(['code' => 200, 'userId' => $authResult->userId]);
        } else $response->setStatusCode(400)->sendJson(['code' => 400, ...$authResult->errors]);
    }
}
