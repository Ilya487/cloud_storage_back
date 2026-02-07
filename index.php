<?php

require_once 'autoloader.php';

use App\Config\Container;
use App\Router\Router;
use App\Tools\ErrorHandler;

error_reporting(E_ERROR);

function executeApp()
{
    $container = Container::getInstance();


    $router = new Router($container);
    $router->resolve();
}

ErrorHandler::handle('executeApp');
die;
