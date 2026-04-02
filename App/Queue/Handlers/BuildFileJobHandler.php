<?php

namespace App\Queue\Handlers;

use App\Db\TransactionManager;
use App\Models\UploadSession;
use App\Models\UploadSessionStatus;
use App\Repositories\FileSystemRepository;
use App\Repositories\UploadSessionRepository;
use App\Repositories\UserRepository;
use App\Storage\DiskStorage;
use App\Storage\FileAssembler;
use App\Storage\UploadsStorage;
use Exception;

class BuildFileJobHandler
{
    public function __construct(
        private FileSystemRepository $fsRepo,
        private UploadSessionRepository $uploadSessionsRepo,
        private UploadsStorage $uploadsStorage,
        private FileAssembler $fileBuilder,
        private DiskStorage $diskStorage,
        private UserRepository $userRepo,
        private TransactionManager $txManager
    ) {}

    public function handle(int $userId, int $sessionId)
    {
        $session = $this->uploadSessionsRepo->getById($userId, $sessionId);
        if ($session === false)
            throw new Exception('Сессия не найдена');

        $this->txManager->withTransaction(function ($rollback) use ($session) {
            $fileId = $this->fsRepo->createFile(
                $session->userId,
                $session->fileName,
                $session->getPath(),
                $session->destinationDirId,
                $session->fileSize
            );

            $ext = strtolower(pathinfo($session->fileName, PATHINFO_EXTENSION));
            $buildedFilePath = $this->diskStorage->createFile($fileId, $ext);
            if ($buildedFilePath == false) {
                $rollback();
                $this->handleError($session, 'Не удалось создать файл на диске', null);
            }

            $buildResult = $this->fileBuilder->buildFile($session, $buildedFilePath);
            if (!$buildResult) {
                $rollback();
                $this->handleError($session, 'Не удалось собрать файл из чанков', $buildedFilePath);
            }
        });

        $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::COMPLETE);
        $this->uploadsStorage->deleteSessionDir($session->id);
    }

    private function handleError(UploadSession $session, string $msg, ?string $buildedFilePath)
    {
        $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::ERROR);
        $this->userRepo->freeUpDiskSpace($session->userId, $session->fileSize);
        $this->uploadsStorage->deleteSessionDir($session->id);
        if (!is_null($buildedFilePath)) unlink($buildedFilePath);
        throw new Exception($msg);
    }
}
