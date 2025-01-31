<?php

namespace App\Router;

use App\Core\DiContainer\Container;
use App\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use Exception;

class Router
{
    /**
     * @var Route[] $routes
     */
    private array $routes = [];
    private array $globalMiddlewares = [];
    private Request $request;

    public function __construct(private Container $container)
    {
        $this->request = $container->resolve(Request::class);
    }

    /**
     * @var Route[] $routes
     */
    public function setRoutes(array $routes)
    {
        $this->routes = array_merge($this->routes, $routes);
    }

    public function setGlobalMiddleware(string $middleware)
    {
        if (!is_subclass_of($middleware, MiddlewareInterface::class)) {
            throw new Exception($middleware . ' не является Middleware');
        }
        $this->globalMiddlewares[] = $middleware;
    }

    public function resolve()
    {
        $this->resolveMiddlewares($this->globalMiddlewares);

        $method = $this->request->method;
        $uri = $this->request->endPoint;

        foreach ($this->routes as $route) {
            if ($route->match($method, $uri)) {
                $this->resolveMiddlewares($route->middlewares);
                $this->resolveController($route->controllerClassName);
            }
        }
    }

    private function resolveMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            $resolvedMiddleware = $this->container->resolve($middleware);
            $resolvedMiddleware->handle();
        }
    }

    private function resolveController(string $className)
    {
        $controller = $this->container->resolve($className);
        $controller->resolve();
    }
}
