<?php

namespace App\Queue\Handlers;

use App\Queue\Jobs\DeleteFilesJob;
use App\Queue\Queue;
use App\Storage\DiskStorage;

class DeleteFilesJobHandler
{
    public function __construct(
        private DiskStorage $disk,
        private Queue $queue
    ) {}

    public function handle(array $filesIds)
    {
        $faildIds = [];

        foreach ($filesIds as $id) {
            if (!$this->disk->isFileExist($id))
                continue;
            if (!$this->disk->delete($id)) {
                $faildIds[] = $id;
            }
        }

        if (!empty($faildIds)) {
            $this->queue->push(DeleteFilesJob::create($faildIds));
            $faildIds = [];
        }
    }
}
