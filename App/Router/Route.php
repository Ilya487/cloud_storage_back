<?php

namespace App\Router;

use App\Config\Container;
use App\Http\Middleware\MiddlewareInterface;
use Exception;

class Route
{
    private string $method;
    private string $pattern;
    private array $routeParams = [];

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

    public function __construct(string $endPoint, string $method, public readonly ControllerSetup $controllerSetup, public readonly array $middlewares = [])
    {
        $this->method = strtolower($method);
        $this->pattern = $this->generateRegExp($endPoint);

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


    public function resolve(string $method, string $uri)
    {
        if (!$this->match($method, $uri)) return;
        $this->resolveMiddlewares();
        $this->resolveController();
    }

    private function match(string $method, string $uri): bool
    {
        if (strtolower($method) == $this->method &&  $this->matchUrl($uri)) return true;
        else return false;
    }

    private function matchUrl(string $url): bool
    {
        $res = preg_match_all($this->pattern, $url, $mathes);
        if (!$res) return false;

        if (count($mathes) > 1) {
            unset($mathes[0]);
            $params =  array_reduce($mathes, function ($carry, $item) {
                $carry[] = $item[0];
                return $carry;
            }, []);
            $this->routeParams = $params;
        }

        return true;
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
        $method = $this->controllerSetup->method;
        $controller->$method(...$this->routeParams);
    }

    private function generateRegExp(string $path)
    {
        $res = str_replace('/', '\/', $path);
        $res = preg_replace('/\{.+?\}/', '([^\/]+)', $res);
        $res = "/^$res$/";
        return $res;
    }
}
