<?php

namespace App\Router;

use App\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

class Router
{
    /**
     * @var Route[] $routes
     */
    private array $routes = [];

    public function __construct(private Request $request, private Response $response) {}

    public function setRoute(Route $route)
    {
        $this->routes[] = $route;
    }

    public function resolve()
    {
        $method = $this->request->method;
        $uri = $this->request->endPoint;

        foreach ($this->routes as $route) {
            if ($route->match($method, $uri)) {
                $this->resolveMiddlewares($route->middlewares);
                $route->controller->resolve($this->request, $this->response);
                return;
            }
        }
    }

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    private function resolveMiddlewares(array $middlewares)
    {
        foreach ($middlewares as $middleware) {
            $middleware->handle($this->request, $this->response);
        }
    }
}
