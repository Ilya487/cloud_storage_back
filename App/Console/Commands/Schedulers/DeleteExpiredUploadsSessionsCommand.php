<?php

namespace App\Console\Commands\Schedulers;

use App\Config\Container;
use App\Scheduler\DeleteExpiredUploadsSessions;
use App\Tools\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'scheduler:delete_expired_uploads')]
class DeleteExpiredUploadsSessionsCommand extends Command
{
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $task = Container::resolve(DeleteExpiredUploadsSessions::class);
        try {
            $task->handle();
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            Logger::writeLogFromError($e);
            return Command::FAILURE;
        }
    }
}
