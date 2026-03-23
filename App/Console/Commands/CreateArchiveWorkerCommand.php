<?php

namespace App\Console\Commands;

use App\Config\Container;
use App\Workers\CreateArchiveWorker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'worker:create_archive')]
class CreateArchiveWorkerCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting create archive worker');
        $worker = Container::resolve(CreateArchiveWorker::class);
        $worker->listen();
        return Command::SUCCESS;
    }
}
