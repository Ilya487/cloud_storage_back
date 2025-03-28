<?php

namespace App\Controllers;

use App\Http\Response;
use App\Controllers\ControllerInterface;

class NotFoundController implements ControllerInterface
{
    public function __construct(private Response $response) {}

    public function resolve(): void
    {
        $this->response->setStatusCode(404)->sendJson(['message' => 'Not Found']);
    }
}
