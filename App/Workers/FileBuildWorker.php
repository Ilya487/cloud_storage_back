<?php

namespace App\Workers;

require_once 'autoloader.php';

use App\Config\Container;
use App\Models\UploadSession;
use App\Models\UploadSessionStatus;
use App\Repositories\FileSystemRepository;
use App\Repositories\UploadSessionRepository;
use App\Storage\DiskStorage;
use App\Storage\FileAssembler;
use App\Storage\UploadsStorage;
use Exception;

class FileBuildWorker
{
    public function __construct(
        private FileSystemRepository $fsRepo,
        private UploadSessionRepository $uploadSessionsRepo,
        private UploadsStorage $uploadsStorage,
        private FileAssembler $fileBuilder,
        private DiskStorage $diskStorage
    ) {}

    public function run(int $sessionId, int $userId)
    {
        $session = $this->uploadSessionsRepo->getById($userId, $sessionId);
        if ($session === false)
            throw new Exception('Сессия не найдена');

        $toDirPath = $session->destinationDirId ? $this->fsRepo->getPathById($session->destinationDirId, $session->userId) : '/';
        $buildedFilePath = $this->getOutputFileName($session, $toDirPath);
        $buildResult = $this->fileBuilder->buildFile($session, $buildedFilePath);

        $this->uploadsStorage->deleteSessionDir($session->id);

        if ($buildResult) {
            $this->fsRepo->createFile(
                $session->userId,
                $session->fileName,
                $toDirPath . '/' . $session->fileName,
                $session->destinationDirId,
                $session->flieSize
            );
            $renameRes = $this->diskStorage->renameObject($session->userId, $session->fileName, $toDirPath . '/' . basename($buildedFilePath));

            if ($renameRes !== false) {
                $this->fsRepo->confirmChanges();
                $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::COMPLETE);
            } else {
                $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::ERROR);
                $this->fsRepo->cancelLastChanges();
            }
        } else {
            $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::ERROR);
            unlink($buildedFilePath);
        }
    }

    private function getOutputFileName(UploadSession $session, string $toDirPath): string
    {
        $buildedFilePath = $this->diskStorage->getPath($session->userId, $toDirPath) . '/.build' . $session->id . $session->fileName;

        return $buildedFilePath;
    }
}

$worker = Container::getInstance()->resolve(FileBuildWorker::class);
['s' => $sessionId, 'u' => $userId] = getopt('s::u::');

$worker->run($sessionId, $userId);
