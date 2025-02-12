<?php

use App\Controllers\AuthCheckController;
use App\Controllers\FolderController;
use App\Controllers\LogOutController;
use App\Controllers\SignInController;
use App\Controllers\SignUpController;
use App\Controllers\UploadController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\ValidationMiddlewares\FileSytemValidationMiddleware;
use App\Http\Middleware\ValidationMiddlewares\UploadValidationMiddleware;
use App\Http\Middleware\ValidationMiddlewares\UserValidationMiddleware;
use App\Router\ControllerSetup;
use App\Router\Route;

return [
    Route::post('/signup', new ControllerSetup(SignUpController::class), [GuestMiddleware::class, [UserValidationMiddleware::class, 'signup']]),
    Route::post('/signin', new ControllerSetup(SignInController::class), [GuestMiddleware::class, [UserValidationMiddleware::class, 'signin']]),
    Route::get('/check-auth', new ControllerSetup(AuthCheckController::class)),
    Route::post('/logout', new ControllerSetup(LogOutController::class), [AuthMiddleware::class]),

    Route::post('/folder', new ControllerSetup(FolderController::class, 'create'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'create']]),
    Route::get('/folder', new ControllerSetup(FolderController::class, 'getFolderContent'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'getContent']]),
    Route::patch('/folder/rename', new ControllerSetup(FolderController::class, 'renameFolder'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'renameFolder']]),
    Route::delete('/folder/delete', new ControllerSetup(FolderController::class, 'delete'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'deleteFolder']]),
    Route::patch('/folder/move', new ControllerSetup(FolderController::class, 'moveFolder'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'moveItem']]),

    Route::post('/upload/init', new ControllerSetup(UploadController::class, 'initUpload'), [AuthMiddleware::class, [UploadValidationMiddleware::class, 'initUpload']])
];
