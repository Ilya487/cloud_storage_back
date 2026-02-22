<?php

use App\Controllers\AuthController;
use App\Controllers\DownloadController;
use App\Controllers\FileSystemController;
use App\Controllers\UploadController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\GuestMiddleware;
use App\Router\ControllerSetup;
use App\Router\Route;

return [
    Route::post('/auth/signup', new ControllerSetup(AuthController::class, 'signup'), [GuestMiddleware::class]),
    Route::post('/auth/signin', new ControllerSetup(AuthController::class, 'signin'), [GuestMiddleware::class]),
    Route::get('/auth/user', new ControllerSetup(AuthController::class, 'getUser')),
    Route::post('/auth/logout', new ControllerSetup(AuthController::class, 'logout'), [AuthMiddleware::class]),
    Route::post('/auth/refresh', new ControllerSetup(AuthController::class, 'refresh'), [GuestMiddleware::class]),

    Route::post('/fs/folder', new ControllerSetup(FileSystemController::class, 'create'), [AuthMiddleware::class]),
    Route::get('/fs/file/{id}/{filename}', new ControllerSetup(FileSystemController::class, 'getFileContent'), [AuthMiddleware::class]),
    Route::get('/fs/folder/id-by-path', new ControllerSetup(FileSystemController::class, 'getFolderIdByPath'), [AuthMiddleware::class]),
    Route::patch('/fs/rename/{id}', new ControllerSetup(FileSystemController::class, 'renameObject'), [AuthMiddleware::class]),
    Route::delete('/fs/delete', new ControllerSetup(FileSystemController::class, 'delete'), [AuthMiddleware::class]),
    Route::patch('/fs/move', new ControllerSetup(FileSystemController::class, 'move'), [AuthMiddleware::class]),
    Route::get('/fs/search', new ControllerSetup(FileSystemController::class, 'search'), [AuthMiddleware::class]),
    Route::get('/fs/folder/{id}', new ControllerSetup(FileSystemController::class, 'getFolderContent'), [AuthMiddleware::class]),
    Route::get('/fs/disk/info', new ControllerSetup(FileSystemController::class, 'getDiskInfo'), [AuthMiddleware::class]),

    Route::post('/upload/init', new ControllerSetup(UploadController::class, 'initUpload'), [AuthMiddleware::class]),
    Route::post('/upload/chunk/{sessionId}', new ControllerSetup(UploadController::class, 'uploadChunk'), [AuthMiddleware::class]),
    Route::delete('/upload/cancel/{sessionId}', new ControllerSetup(UploadController::class, 'cancelUpload'), [AuthMiddleware::class]),
    Route::post('/upload/{sessionId}/build', new ControllerSetup(UploadController::class, 'startBuild'), [AuthMiddleware::class]),
    Route::get('/upload/status/{sessionId}', new ControllerSetup(UploadController::class, 'checkStatus'), [AuthMiddleware::class]),

    Route::get('/download/file/{id}', new ControllerSetup(DownloadController::class, 'downloadFile'), [AuthMiddleware::class]),
    Route::post('/download/archive/ini', new ControllerSetup(DownloadController::class, 'iniArchive'), [AuthMiddleware::class]),
    Route::get('/download/archive/status/{id}', new ControllerSetup(DownloadController::class, 'checkArchiveStatus'), [AuthMiddleware::class]),
    Route::get('/download/archive/{id}', new ControllerSetup(DownloadController::class, 'downloadArchive'), [AuthMiddleware::class]),
];
