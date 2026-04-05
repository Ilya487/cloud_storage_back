<?php

namespace App\Scheduler;

use App\Repositories\UploadSessionRepository;
use App\Storage\UploadsStorage;

class DeleteExpiredUploadsSessions
{

    private const DELETE_LIMIT = 100;

    public function __construct(
        private UploadSessionRepository $uploadRepo,
        private UploadsStorage $uploadStorage
    ) {}

    public function handle()
    {
        $uploads = $this->uploadRepo->getExpired(self::DELETE_LIMIT);
        if ($uploads->len() == 0) return;
        $sessionsForDelete =  [];

        foreach ($uploads as $uploadSession) {
            $delRes = $this->uploadStorage->deleteSessionDir($uploadSession->id);
            if ($delRes !== false) $sessionsForDelete[] = $uploadSession->id;
        }

        if (count($sessionsForDelete) > 0)
            $this->uploadRepo->deleteSessionsByIds($sessionsForDelete);
    }
}
