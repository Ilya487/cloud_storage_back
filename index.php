<?php

require_once 'autoloader.php';

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
    $container = Container::getInstance();


    $router = new Router($container);
    $router->resolve();
}

ErrorHandler::handle('executeApp');
die;
