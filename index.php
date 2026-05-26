<?php

require_once 'vendor/autoload.php';

use App\Config\Container;
use App\Router\Router;
use App\Tools\ErrorHandler;

error_reporting(E_ERROR);


function auth(): ?User
{
    return Container::resolve(AuthManager::class)->getAuthUser();
}

function executeApp()
{
    $router = Container::resolve(Router::class);
    $router->setGlobalMiddleware(CORSMiddleware::class);

    $router->handleRequest();
}

ErrorHandler::handle('executeApp');
die;
