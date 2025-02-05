<?php

namespace App\Router;

use App\Controllers\ControllerInterface;
use App\Http\Middleware\MiddlewareInterface;
use Exception;

class Route
{
    private string $method;

    public function __construct(private string $endPoint, string $method, public readonly string $controllerClassName, public readonly array $middlewares = [])
    {
        $this->method = strtolower($method);

        if (!is_subclass_of($controllerClassName, ControllerInterface::class)) {
            throw new Exception($controllerClassName . ' не является Controller');
        }

        foreach ($middlewares as $middleware) {
            if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
                throw new Exception($middleware . ' не является Middleware');
            }
        }
    }

    public function match(string $method, string $uri): bool
    {
        if (strtolower($method) == $this->method && $uri == $this->endPoint) return true;
        else return false;
    }
}
