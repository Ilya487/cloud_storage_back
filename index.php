<?php
spl_autoload_register();

use App\Authentication\AuthenticationInterface;
use App\Authentication\SessionAuthentication;
use App\Controllers\AuthCheckController;
use App\Controllers\FolderController;
use App\Controllers\LogOutController;
use App\Controllers\SignInController;
use App\Controllers\SignUpController;
use App\Core\DiContainer\ContainerBuilder;
use App\Core\DiContainer\ContainerParam;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\OptionsRequestMiddleware;
use App\Http\Middleware\ValidationMiddlewares\FileSytemValidationMiddleware;
use App\Http\Middleware\ValidationMiddlewares\UserValidationMiddleware;
use App\Repositories\FileSystemRepository;
use App\Repositories\UserRepository;
use App\Router\ControllerSetup;
use App\Router\Route;
use App\Router\Router;
use App\Storage\DiskStorage;
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
    $containerBuilder->setParam(new ContainerParam(UserRepository::class, 'tableName', 'users'));
    $containerBuilder->setParam(new ContainerParam(FileSystemRepository::class, 'tableName', 'file_system'));

    $container = $containerBuilder->build();


    $router = new Router($container);

    $router->setGlobalMiddleware(OptionsRequestMiddleware::class);
    $router->setRoutes([
        new Route('/signup', 'POST', new ControllerSetup(SignUpController::class), [GuestMiddleware::class, [UserValidationMiddleware::class, 'signup']]),
        new Route('/signin', 'POST', new ControllerSetup(SignInController::class), [GuestMiddleware::class, [UserValidationMiddleware::class, 'signin']]),
        new Route('/check-auth', 'GET', new ControllerSetup(AuthCheckController::class)),
        new Route('/logout', 'POST', new ControllerSetup(LogOutController::class), [AuthMiddleware::class]),

        new Route('/folder', 'POST', new ControllerSetup(FolderController::class, 'create'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'create']]),
        new Route('/folder', 'GET', new ControllerSetup(FolderController::class, 'getFolderContent'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'getContent']]),
        new Route('/folder/rename', 'PATCH', new ControllerSetup(FolderController::class, 'renameFolder'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'renameFolder']]),
        new Route('/folder/delete', 'DELETE', new ControllerSetup(FolderController::class, 'delete'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'deleteFolder']]),
        new Route('/folder/move', 'PATCH', new ControllerSetup(FolderController::class, 'moveFolder'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'moveItem']]),
    ]);
    $router->resolve();
}

ErrorHandler::handle('executeApp');
