<?php

namespace App\Console\Commands;

use App\Config\Container;
use App\Workers\FileBuildWorker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'worker:build_file')]
class BuildFileWorkerCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting file build worker');
        $worker = Container::resolve(FileBuildWorker::class);
        $worker->listen();
        return Command::SUCCESS;
    }
}
