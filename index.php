<?php
spl_autoload_register();

use App\Authentication\SessionAuthentication;
use App\Controllers\SignInController;
use App\Controllers\SignUpController;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Router\Route;
use App\Router\Router;

function executeApp()
{
    $request = new Request;
    $response = new Response;
    $authService = new SessionAuthentication;

    $router = new Router($request, $response);

    $signUpRoute = new Route('/signup', 'POST', new SignUpController, [new GuestMiddleware($authService)]);
    $signInRoute = new Route('/signin', 'POST', new SignInController, [new GuestMiddleware($authService)]);

    $router->setRoute($signInRoute);
    $router->setRoute($signUpRoute);

    $router->resolve();
}

executeApp();
