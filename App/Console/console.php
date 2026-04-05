<?php

use App\Console\Commands\BuildFileWorkerCommand;
use App\Console\Commands\CreateArchiveWorkerCommand;
use App\Console\Commands\DeleteFilesWorkerCommand;
use App\Console\Commands\Schedulers\DeleteArchivesCommand;
use App\Console\Commands\Schedulers\DeleteExpiredAuthTokensCommand;
use App\Console\Commands\Schedulers\DeleteExpiredUploadsSessionsCommand;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../../vendor/autoload.php';

error_reporting(E_ERROR | E_COMPILE_ERROR);

$app = new Application();
$app->addCommands([
    new CreateArchiveWorkerCommand(),
    new BuildFileWorkerCommand(),
    new DeleteFilesWorkerCommand(),
    new DeleteArchivesCommand(),
    new DeleteExpiredUploadsSessionsCommand(),
    new DeleteExpiredAuthTokensCommand
]);
$app->run();
