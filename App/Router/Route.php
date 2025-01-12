<?php

namespace App\Router;

class Route
{
    private string $method;

    public function __construct(private string $endPoint, string $method, public readonly string $controllerClassName, public readonly array $middlewares = [])
    {
        $this->method = strtolower($method);
    }

    public function match(string $method, string $uri): bool
    {
        if (strtolower($method) == $this->method && $uri == $this->endPoint) return true;
        else return false;
    }
}
