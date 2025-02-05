<?php

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

class JsonValidationMiddleware implements MiddlewareInterface
{
    public function __construct(private Request $request, private Response $response) {}

    public function handle()
    {
        $data = $this->request->json();
        if (is_null($data)) {
            $this->response->setStatusCode(400)->sendJson(['message' => 'Неверный JSON']);
        }
    }
}
