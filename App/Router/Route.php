<?php

namespace App\Router;

use App\Controllers\ControllerInterface;

class Route
{
    private string $method;

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(private string $endPoint, string $method, public readonly ControllerInterface $controller, public readonly array $middlewares = [])
    {
        $this->method = strtolower($method);
    }

    public function match(string $method, string $uri): bool
    {
        if (strtolower($method) == $this->method && $uri == $this->endPoint) return true;
        else return false;
    }
}
