<?php

namespace App\Router;

use App\Config\Container;
use App\Http\Middleware\MiddlewareInterface;
use Exception;

class Route
{
    private string $method;

    public static function get(string $endPoint, ControllerSetup $controllerSetup, array $middlewares = [])
    {
        return new self($endPoint, 'GET', $controllerSetup, $middlewares);
    }

    public static function post(string $endPoint, ControllerSetup $controllerSetup, array $middlewares = [])
    {
        return new self($endPoint, 'POST', $controllerSetup, $middlewares);
    }

    public static function patch(string $endPoint, ControllerSetup $controllerSetup, array $middlewares = [])
    {
        return new self($endPoint, 'PATCH', $controllerSetup, $middlewares);
    }

    public static function delete(string $endPoint, ControllerSetup $controllerSetup, array $middlewares = [])
    {
        return new self($endPoint, 'DELETE', $controllerSetup, $middlewares);
    }

    public static function all(ControllerSetup $controllerSetup)
    {
        return new self('*', '*', $controllerSetup);
    }

    public function __construct(private string $endPoint, string $method, public readonly ControllerSetup $controllerSetup, public readonly array $middlewares = [])
    {
        $this->method = strtolower($method);

        foreach ($middlewares as $middleware) {
            if (is_array($middleware)) {
                $className = $middleware[0];
                $method = $middleware[1];

                if (!is_subclass_of($className, MiddlewareInterface::class)) {
                    throw new Exception($middleware . ' не является Middleware');
                }

                if (!method_exists($className, $method)) {
                    throw new Exception("У класса $className отсутствует метод $method");
                }
            } else {
                if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
                    throw new Exception($middleware . ' не является Middleware');
                }
            }
        }
    }

    public function match(string $method, string $uri): bool
    {
        if ($this->method == '*' && $this->endPoint == '*') return true;
        if (strtolower($method) == $this->method && $uri == $this->endPoint) return true;
        else return false;
    }

    public function resolve()
    {
        $this->resolveMiddlewares();
        $this->resolveController();
    }

    private function resolveMiddlewares()
    {
        $container = Container::getInstance();
        foreach ($this->middlewares as $middleware) {
            if (is_array($middleware)) {
                $resolvedMiddleware = $container->resolve($middleware[0]);
                $method = $middleware[1];
                $resolvedMiddleware->$method();
            } else {
                $resolvedMiddleware = $container->resolve($middleware);
                $resolvedMiddleware->handle();
            }
        }
    }

    private function resolveController()
    {
        $container = Container::getInstance();
        $controller = $container->resolve($this->controllerSetup->controllerClassName);
        call_user_func([$controller, $this->controllerSetup->method]);
    }
}
