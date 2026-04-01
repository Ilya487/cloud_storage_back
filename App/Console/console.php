<?php

use App\Console\Commands\BuildFileWorkerCommand;
use App\Console\Commands\CreateArchiveWorkerCommand;
use App\Console\Commands\DeleteArchivesSchedulerCommand;
use App\Console\Commands\DeleteFilesWorkerCommand;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../../vendor/autoload.php';

error_reporting(E_ERROR | E_COMPILE_ERROR);

$app = new Application();
$app->addCommands([
    new CreateArchiveWorkerCommand(),
    new BuildFileWorkerCommand(),
    new DeleteFilesWorkerCommand(),
    new DeleteArchivesSchedulerCommand()
]);
$app->run();
