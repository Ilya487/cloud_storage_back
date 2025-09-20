<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Models\UploadSession;
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
        $this->deleteExpiredSessions($userId);
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
        $uploadSessionId = $this->uploadSessionsRepo->createUploadSession($userId, $fileName, $totalChunks, $destinationDirId);
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
        if (!$uploadSession || $uploadSession->userId !== $userId) {
            return OperationResult::createError(['message' => 'Сессия с таким айди не найдена']);
        }

        if (!$this->uploadsStorage->uploadChunk($uploadSessionId, $chunkNum, $data)) {
            return OperationResult::createError(['message' => 'Не удалось загрузить чанк']);
        }

        $completedChunksCount = $uploadSession->incrementCompletedChunks();
        $this->uploadSessionsRepo->updateCompletedChunks($uploadSessionId, $completedChunksCount);

        if ($uploadSession->isUploadComplete()) {
            return $this->finalizeUpload($uploadSession);
        }

        return OperationResult::createSuccess(['progress' => $uploadSession->getProgress()]);
    }

    public function cancelUploadSession(int $userId, int $uploadSessionId): OperationResult
    {
        $uploadSession = $this->uploadSessionsRepo->getById($userId, $uploadSessionId);
        if (!$uploadSession || $uploadSession->userId !== $userId) {
            return OperationResult::createError(['message' => 'Сессия с таким айди не найдена']);
        }

        [$buildedFilePath] = $this->getOutputFileName($uploadSession);
        unlink($buildedFilePath);
        $this->uploadsStorage->deleteSessionDir($uploadSession->id);
        $this->uploadSessionsRepo->deleteSession($userId, $uploadSession->id);
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

    private function finalizeUpload(UploadSession $uploadSession): OperationResult
    {
        $fileId = $this->buildFile($uploadSession);
        if (!$fileId) {
            return OperationResult::createError(['message' => 'Не удалось собрать файл']);
        }

        return OperationResult::createSuccess([
            'progress' => $uploadSession->getProgress(),
            'message' => 'Файл успешно загружен',
            'fileId' => $fileId,
            'parentDirId' => $uploadSession->destinationDirId
        ]);
    }

    private function buildFile(UploadSession $session): int|false
    {
        set_time_limit(120);

        [$buildedFilePath, $toDirPath] = $this->getOutputFileName($session);
        $buildResult = $this->fileBuilder->buildFile($session, $buildedFilePath);

        $this->uploadSessionsRepo->deleteSession($session->userId, $session->id);
        $this->uploadsStorage->deleteSessionDir($session->id);

        if ($buildResult->success) {
            $id = $this->fsRepo->createFile(
                $session->userId,
                $session->fileName,
                $toDirPath . '/' . $session->fileName,
                $session->destinationDirId,
                $buildResult->fileSize
            );
            $this->diskStorage->renameObject($session->userId, $session->fileName, $toDirPath . '/' . basename($buildedFilePath));
            $this->fsRepo->confirmChanges();
            return $id;
        } else {
            unlink($buildedFilePath);
            return false;
        }
    }

    private function getOutputFileName(UploadSession $session): array
    {
        $toDirPath = $session->destinationDirId ? $this->fsRepo->getPathById($session->destinationDirId, $session->userId) : '/';
        $buildedFilePath = $this->diskStorage->getPath($session->userId, $toDirPath) . '/.build' . $session->id . $session->fileName;

        return [$buildedFilePath, $toDirPath];
    }
}
