<?php

namespace App\Router;

use App\Config\Container;
use App\Exceptions\NotFoundException;
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

    public function __construct(private Request $request)
    {
        $this->routes = $this->loadRoutesFromConfig();
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
        $this->resolveGlobalMiddlewares($this->globalMiddlewares);

        $method = $this->request->method;
        $uri = $this->request->endPoint;

        foreach ($this->routes as $route) {
            $route->resolve($method, $uri);
        }

        throw new NotFoundException('Not found');
    }

    private function resolveGlobalMiddlewares()
    {
        foreach ($this->globalMiddlewares as $middleware) {
            $container = Container::getInstance();
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

    private function loadRoutesFromConfig(): array
    {
        $routes = require __DIR__ . '/routs.config.php';
        return $routes;
    }
}
