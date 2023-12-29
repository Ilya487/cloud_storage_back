<?php
spl_autoload_register();

class Router
{
    private const CONTROLLERS_NAMESPACE = 'App\Controllers\\';

    private static $routes = [
        [
            'path' => '/^\/signup$/',
            'controller' => 'SignUpController'
        ],
    ];

    public static function resolvePath()
    {
        $requestUrl = $_SERVER['REQUEST_URI'];

        foreach (self::$routes as $route) {
            $cmpRes = preg_match($route['path'], $requestUrl);

            if ($cmpRes) {
                $classname = self::CONTROLLERS_NAMESPACE . $route['controller'];
                $controller = new $classname;
                self::callController($controller);
                break;
            }
        }
    }

    private static function callController(\App\Contracts\Controller $controller)
    {
        $controller->resolve();
    }
}

Router::resolvePath();
