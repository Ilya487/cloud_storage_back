<?php

namespace App\Workers;

use App\Config\Container;
use App\Repositories\FilesToDeleteQueueRepository;
use App\Storage\DiskStorage;
use App\Tools\ErrorHandler;

require_once 'autoloader.php';

class DeleteFilesWorker
{
    public function __construct(
        private FilesToDeleteQueueRepository $deleteQueue,
        private DiskStorage $disk
    ) {}

    public function run()
    {
        $ids = $this->deleteQueue->getIds(100);
        if ($ids === false) return;

        $idsToDelete = [];
        foreach ($ids as $id) {
            if (!$this->disk->isFileExist($id))
                $idsToDelete[] = $id;
            elseif ($this->disk->delete($id))
                $idsToDelete[] = $id;
        }

        $this->deleteQueue->deleteIds($idsToDelete);
    }
}

ErrorHandler::handle(function () {
    $worker = Container::getInstance()->resolve(DeleteFilesWorker::class);
    $worker->run();
});
