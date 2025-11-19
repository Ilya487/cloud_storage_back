<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Models\UploadSession;
use App\Models\UploadSessionStatus;
use App\Repositories\FileSystemRepository;
use App\Repositories\UploadSessionRepository;
use App\Storage\DiskStorage;
use App\Storage\FileAssembler;
use App\Storage\UploadsStorage;

class UploadService
{
    private const CHUNK_SIZE = 8 * 1024 * 1024;
    private const MAX_ACTIVE_SESSION_FOR_USER = 5;
    private const SESSION_MAX_LIFETIME = 300; //5min

    public function __construct(
        private FileSystemRepository $fsRepo,
        private UploadSessionRepository $uploadSessionsRepo,
        private UploadsStorage $uploadsStorage,
        private FileAssembler $fileBuilder,
        private DiskStorage $diskStorage
    ) {}

    public function initializeUploadSession(int $userId, string $fileName, int $fileSize, ?int $destinationDirId): OperationResult
    {
        if ($this->uploadSessionsRepo->getUserSessionsCount($userId) == self::MAX_ACTIVE_SESSION_FOR_USER) {
            return OperationResult::createError(['message' => 'Вы превысили максимальное количество активных сессий']);
        }

        if (!is_null($destinationDirId)) {
            $destinationDirPath = $this->fsRepo->getPathById($destinationDirId, $userId);
            if ($destinationDirPath === false) {
                return OperationResult::createError(['message' => 'Указана неверная папка назначения']);
            }
        } else $destinationDirPath = '/';

        if (
            $this->fsRepo->isNameExist($userId, $fileName, $destinationDirId) ||
            $this->uploadSessionsRepo->isNameExist($userId, $fileName, $destinationDirId)
        ) {
            return OperationResult::createError(['message' => 'Файл с таким именем уже существует!']);
        }

        $totalChunks = ceil($fileSize / self::CHUNK_SIZE);
        $uploadSessionId = $this->uploadSessionsRepo->createUploadSession($userId, $fileName, $totalChunks, $destinationDirId, $fileSize);
        if (!$this->uploadsStorage->initializeUploadDir($uploadSessionId)) {
            $this->uploadSessionsRepo->deleteSession($userId, $uploadSessionId);
            return OperationResult::createError(['message' => 'Не удалась инициализировать сессию загрузки']);
        }

        return OperationResult::createSuccess([
            'sessionId' => (int)$uploadSessionId,
            'chunkSize' => self::CHUNK_SIZE,
            'chunksCount' => $totalChunks,
            'path' => $destinationDirPath
        ]);
    }

    public function uploadChunk(int $userId, int $uploadSessionId, int $chunkNum, string $data): OperationResult
    {
        $uploadSession = $this->uploadSessionsRepo->getById($userId, $uploadSessionId);
        if ($uploadSession === false) {
            return OperationResult::createError(['message' => 'Сессия с данным айди не найдена']);
        }

        if ($uploadSession->isUploadComplete()) {
            return OperationResult::createError(['message' => 'Все чанки уже загружены']);
        }

        if (!$this->uploadsStorage->uploadChunk($uploadSessionId, $chunkNum, $data)) {
            return OperationResult::createError(['message' => 'Не удалось загрузить чанк']);
        }

        $count = $this->uploadSessionsRepo->incrementCompletedChunks($uploadSessionId, 23);
        $uploadSession->setChunks($count);

        return OperationResult::createSuccess(['progress' => $uploadSession->getProgress()]);
    }

    public function cancelUploadSession(int $userId, int $uploadSessionId): OperationResult
    {
        $uploadSession = $this->uploadSessionsRepo->getById($userId, $uploadSessionId);
        if ($uploadSession === false) {
            return OperationResult::createError(['message' => 'Сессия с данным айди не найдена']);
        }

        [$buildedFilePath] = $this->getOutputFileName($uploadSession);
        unlink($buildedFilePath);
        $this->uploadsStorage->deleteSessionDir($uploadSession->id);
        $this->uploadSessionsRepo->setStatus($userId, $uploadSession->id, UploadSessionStatus::CANCELLED);
        return OperationResult::createSuccess([]);
    }

    private function deleteExpiredSessions($userId)
    {
        $sessions = $this->uploadSessionsRepo->getUserSessions($userId);
        foreach ($sessions as $session) {
            $timeDiff = time() - $session->lastUpdated->getTimestamp();
            if ($timeDiff >= self::SESSION_MAX_LIFETIME) {
                $this->cancelUploadSession($session->userId, $session->id);
            }
        }
    }

    public function finalizeUpload(int $userId, int $uploadSessionId): OperationResult
    {
        $uploadSession = $this->uploadSessionsRepo->getById($userId, $uploadSessionId);
        if ($uploadSession === false) return OperationResult::createError(['message' => 'Сессия с данным айди не найдена']);

        $fileId = $this->buildFile($uploadSession);
        if (!$fileId) {
            return OperationResult::createError(['message' => 'Не удалось собрать файл']);
        }

        return OperationResult::createSuccess([
            'message' => 'Файл успешно загружен',
            'fileId' => $fileId,
            'parentDirId' => $uploadSession->destinationDirId
        ]);
    }

    private function buildFile(UploadSession $session): int|false
    {
        set_time_limit(0);

        [$buildedFilePath, $toDirPath] = $this->getOutputFileName($session);
        $buildResult = $this->fileBuilder->buildFile($session, $buildedFilePath);

        $this->uploadsStorage->deleteSessionDir($session->id);

        if ($buildResult) {
            $id = $this->fsRepo->createFile(
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
                return $id;
            } else {
                $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::ERROR);
                $this->fsRepo->cancelLastChanges();
            }
        }

        $this->uploadSessionsRepo->setStatus($session->userId, $session->id, UploadSessionStatus::ERROR);
        unlink($buildedFilePath);
        return false;
    }

    private function getOutputFileName(UploadSession $session): array
    {
        $toDirPath = $session->destinationDirId ? $this->fsRepo->getPathById($session->destinationDirId, $session->userId) : '/';
        $buildedFilePath = $this->diskStorage->getPath($session->userId, $toDirPath) . '/.build' . $session->id . $session->fileName;

        return [$buildedFilePath, $toDirPath];
    }
}
