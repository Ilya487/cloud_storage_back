<?php

namespace App\Scheduler;

use App\Db\TransactionManager;
use App\Repositories\UploadSessionRepository;
use App\Repositories\UserRepository;
use App\Storage\UploadsStorage;

class DeleteExpiredUploadsSessions
{

    private const DELETE_LIMIT = 100;

    public function __construct(
        private UploadSessionRepository $uploadRepo,
        private UploadsStorage $uploadStorage,
        private UserRepository $userRepo,
        private TransactionManager $txManager
    ) {}

    public function handle()
    {
        $uploads = $this->uploadRepo->getExpired(self::DELETE_LIMIT);
        if ($uploads->len() == 0) return;
        $sessionsForDelete =  [];
        $spaceToFreeUp = [];

        foreach ($uploads as $uploadSession) {
            $delRes = $this->uploadStorage->deleteSessionDir($uploadSession->id);
            if ($delRes === true) {
                $sessionsForDelete[] = $uploadSession->id;
                if ($uploadSession->isUploading()) {
                    $spaceToFreeUp[$uploadSession->userId] += $uploadSession->fileSize;
                }
            }
        }

        $this->txManager->withTransaction(function () use ($sessionsForDelete, $spaceToFreeUp) {
            if (count($sessionsForDelete) > 0)
                $this->uploadRepo->deleteSessionsByIds($sessionsForDelete);
            if ($spaceToFreeUp > 0) {
                foreach ($spaceToFreeUp as $userId => $space)
                    $this->userRepo->freeUpDiskSpace($userId, $space);
            }
        });
    }
}
