<?php

namespace App\Workers;

require_once 'autoloader.php';

use App\Config\Container;
use App\Models\UploadSession;
use App\Models\UploadSessionStatus;
use App\Repositories\FileSystemRepository;
use App\Repositories\UploadSessionRepository;
use App\Repositories\UserRepository;
use App\Storage\DiskStorage;
use App\Storage\FileAssembler;
use App\Storage\UploadsStorage;
use App\Tools\ErrorHandler;
use Exception;

class FileBuildWorker
{
    public function __construct(
        private FileSystemRepository $fsRepo,
        private UploadSessionRepository $uploadSessionsRepo,
        private UploadsStorage $uploadsStorage,
        private FileAssembler $fileBuilder,
        private DiskStorage $diskStorage,
        private UserRepository $userRepo
    ) {}

    public function run(int $sessionId, int $userId)
    {
        $session = $this->uploadSessionsRepo->getById($userId, $sessionId);
        if ($session === false)
            throw new Exception('Сессия не найдена');

        $this->fsRepo->withTransaction(function ($commit, $rollback) use ($session) {
            $fileId = $this->fsRepo->createFile(
                $session->userId,
                $session->fileName,
                $session->destinationDirPath . '/' . $session->fileName,
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

            $commit();
            $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::COMPLETE);
            $this->uploadsStorage->deleteSessionDir($session->id);
        });
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

ErrorHandler::handle(function () {
    $worker = Container::getInstance()->resolve(FileBuildWorker::class);
    ['s' => $sessionId, 'u' => $userId] = getopt('s::u::');

    $worker->run($sessionId, $userId);
});
