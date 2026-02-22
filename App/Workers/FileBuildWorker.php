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

        $buildedFilePath = $this->getOutputFileName($session, $session->destinationDirPath);
        $buildResult = $this->fileBuilder->buildFile($session, $buildedFilePath);

        $this->uploadsStorage->deleteSessionDir($session->id);

        if (!$buildResult) {
            $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::ERROR);
            $this->userRepo->freeUpDiskSpace($session->userId, $session->flieSize);
            unlink($buildedFilePath);
            return;
        }

        if ($session->destinationDirPath == '/')
            $destinationDirId = null;
        else
            $destinationDirId = $this->fsRepo->getDirIdByPath($userId, $session->destinationDirPath);

        $this->fsRepo->createFile(
            $session->userId,
            $session->fileName,
            $session->destinationDirPath . '/' . $session->fileName,
            $destinationDirId,
            $session->flieSize
        );

        $renameRes = $this->diskStorage->renameObject($session->userId, $session->fileName, $session->destinationDirPath . '/' . basename($buildedFilePath));

        if ($renameRes !== false) {
            $this->fsRepo->confirmChanges();
            $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::COMPLETE);
        } else {
            $this->fsRepo->cancelLastChanges();
            $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::ERROR);
            $this->userRepo->freeUpDiskSpace($session->userId, $session->flieSize);
        }
    }

    private function getOutputFileName(UploadSession $session, string $toDirPath): string
    {
        $buildedFilePath = $this->diskStorage->getPath($session->userId, $toDirPath) . '/.build' . $session->id . $session->fileName;

        return $buildedFilePath;
    }
}

ErrorHandler::handle(function () {
    $worker = Container::getInstance()->resolve(FileBuildWorker::class);
    ['s' => $sessionId, 'u' => $userId] = getopt('s::u::');

    $worker->run($sessionId, $userId);
});
