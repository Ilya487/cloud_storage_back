<?php

use App\Controllers\AuthCheckController;
use App\Controllers\DownloadController;
use App\Controllers\FolderController;
use App\Controllers\LogOutController;
use App\Controllers\NotFoundController;
use App\Controllers\SignInController;
use App\Controllers\SignUpController;
use App\Controllers\UploadController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\ValidationMiddlewares\DownloadValidationMiddleware;
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
    Route::patch('/rename', new ControllerSetup(FolderController::class, 'renameObject'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'renameFolder']]),
    Route::delete('/delete', new ControllerSetup(FolderController::class, 'delete'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'delete']]),
    Route::patch('/move', new ControllerSetup(FolderController::class, 'move'), [AuthMiddleware::class, [FileSytemValidationMiddleware::class, 'moveItem']]),

    Route::post('/upload/init', new ControllerSetup(UploadController::class, 'initUpload'), [AuthMiddleware::class, [UploadValidationMiddleware::class, 'initUpload']]),
    Route::post('/upload/chunk', new ControllerSetup(UploadController::class, 'uploadChunk'), [AuthMiddleware::class, [UploadValidationMiddleware::class, 'uploadChunk']]),
    Route::delete('/upload/cancel', new ControllerSetup(UploadController::class, 'cancelUpload'), [AuthMiddleware::class, [UploadValidationMiddleware::class, 'cancelUpload']]),

    Route::get('/download', new ControllerSetup(DownloadController::class), [AuthMiddleware::class, DownloadValidationMiddleware::class]),

    Route::all(new ControllerSetup(NotFoundController::class))
];
