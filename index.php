<?php
spl_autoload_register();

use App\Authentication\AuthenticationInterface;
use App\Authentication\SessionAuthentication;
use App\Controllers\SignInController;
use App\Controllers\SignUpController;
use App\Core\DiContainer\ContainerBuilder;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Request;
use App\Router\Route;
use App\Router\Router;
use App\Tools\DbConnect;
use App\Tools\Session;

function executeApp()
{
    $containerBuilder = new ContainerBuilder;
    $containerBuilder->bind(AuthenticationInterface::class, SessionAuthentication::class);
    $containerBuilder->share(DbConnect::class);
    $containerBuilder->share(Session::class);
    $container = $containerBuilder->build();


    $request = $container->resolve(Request::class);
    $router = new Router($container, $request);
    $signUpRoute = new Route('/signup', 'POST', SignUpController::class, [GuestMiddleware::class]);
    $signInRoute = new Route('/signin', 'POST', SignInController::class, [GuestMiddleware::class]);

    $router->setRoutes([$signUpRoute, $signInRoute]);
    $router->resolve();
}

executeApp();
