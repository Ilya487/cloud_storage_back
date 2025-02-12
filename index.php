<?php
spl_autoload_register();

use App\Authentication\AuthenticationInterface;
use App\Authentication\SessionAuthentication;
use App\Core\DiContainer\ContainerBuilder;
use App\Core\DiContainer\ContainerParam;
use App\Http\Middleware\OptionsRequestMiddleware;
use App\Repositories\FileSystemRepository;
use App\Repositories\UploadSessionRepository;
use App\Repositories\UserRepository;
use App\Router\Router;
use App\Storage\DiskStorage;
use App\Storage\UploadsStorage;
use App\Tools\DbConnect;
use App\Tools\ErrorHandler;
use App\Tools\Session;

function executeApp()
{
    $containerBuilder = new ContainerBuilder;
    $containerBuilder->bind(AuthenticationInterface::class, SessionAuthentication::class);
    $containerBuilder->share(DbConnect::class);
    $containerBuilder->share(Session::class);
    $containerBuilder->setParam(new ContainerParam(DiskStorage::class, 'storagePath', 'C:\Users\Илья\Desktop\storage'));
    $containerBuilder->setParam(new ContainerParam(UploadsStorage::class, 'storagePath', 'C:\Users\Илья\Desktop\storage'));
    $containerBuilder->setParam(new ContainerParam(UserRepository::class, 'tableName', 'users'));
    $containerBuilder->setParam(new ContainerParam(FileSystemRepository::class, 'tableName', 'file_system'));
    $containerBuilder->setParam(new ContainerParam(UploadSessionRepository::class, 'tableName', 'upload_sessions'));

    $container = $containerBuilder->build();


    $router = new Router($container);
    $router->setGlobalMiddleware(OptionsRequestMiddleware::class);
    $router->resolve();
}

ErrorHandler::handle('executeApp');
