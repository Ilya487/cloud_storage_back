<?php

use App\Console\Commands\BuildFileWorkerCommand;
use App\Console\Commands\CreateArchiveWorkerCommand;
use App\Console\Commands\DeleteFilesWorkerCommand;
use Symfony\Component\Console\Application;

require_once __DIR__ . '/../../vendor/autoload.php';

error_reporting(E_ERROR);

$app = new Application();
$app->addCommands([
    new CreateArchiveWorkerCommand(),
    new BuildFileWorkerCommand(),
    new DeleteFilesWorkerCommand()
]);
$app->run();
