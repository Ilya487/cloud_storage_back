<?php

namespace App\Router;

use App\Controllers\ControllerInterface;
use App\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;

class Router
{
    /**
     * @var Route[] $routs
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
                $this->callController(new $route->controllerClassName);
                return;
            }
        }
    }

    private function callController(ControllerInterface $controller)
    {
        $controller->resolve($this->request, $this->response);
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
