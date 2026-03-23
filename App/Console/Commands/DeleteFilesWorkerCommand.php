<?php

namespace App\Console\Commands;

use App\Config\Container;
use App\Workers\DeleteFilesWorker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'worker:delete_files')]
class DeleteFilesWorkerCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting delete files worker');
        $worker = Container::resolve(DeleteFilesWorker::class);
        $worker->listen();
        return Command::SUCCESS;
    }
}
