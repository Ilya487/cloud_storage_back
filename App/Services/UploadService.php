<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Models\UploadSession;
use App\Repositories\FileSystemRepository;
use App\Repositories\UploadSessionRepository;
use App\Storage\FileAssembler;
use App\Storage\UploadsStorage;

class UploadService
{
    private const CHUNK_SIZE = 7340032; //7mb
    private const MAX_ACTIVE_SESSION_FOR_USER = 5;
    private const SESSION_MAX_LIFETIME = 300; //5min

    public function __construct(
        private FileSystemRepository $fsRepo,
        private UploadSessionRepository $uploadSessionsRepo,
        private UploadsStorage $uploadsStorage,
        private FileAssembler $fileBuilder
    ) {}

    public function initializeUploadSession(int $userId, string $fileName, int $fileSize, ?int $destinationDirId): OperationResult
    {
        $this->deleteExpiredSessions($userId);
        if ($this->uploadSessionsRepo->getUserSessionsCount($userId) == self::MAX_ACTIVE_SESSION_FOR_USER) {
            return new OperationResult(false, null, ['message' => 'Вы превысили максимальное количество активных сессий']);
        }

        if (!is_null($destinationDirId)) {
            $destinationDirPath = $this->fsRepo->getPathById($destinationDirId, $userId);
            if ($destinationDirPath === false) {
                return new OperationResult(false, null, ['message' => 'Указана неверная папка назначения']);
            }
        } else $destinationDirPath = '/';

        if (
            $this->fsRepo->isNameExist($userId, $fileName, $destinationDirId) ||
            $this->uploadSessionsRepo->isNameExist($userId, $fileName, $destinationDirId)
        ) {
            return new OperationResult(false, null, ['message' => 'Файл с таким именем уже существует!']);
        }

        $totalChunks = ceil($fileSize / self::CHUNK_SIZE);
        $uploadSessionId = $this->uploadSessionsRepo->createUploadSession($userId, $fileName, $totalChunks, $destinationDirId);
        if (!$this->uploadsStorage->initializeUploadDir($uploadSessionId)) {
            $this->uploadSessionsRepo->deleteSession($userId, $uploadSessionId);
            return new OperationResult(false, null, ['message' => 'Не удалась инициализировать сессию загрузки']);
        }

        return new OperationResult(true, [
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
            return new OperationResult(false, null, ['message' => 'Сессия с таким айди не найдена']);
        }

        if (!$this->uploadsStorage->uploadChunk($uploadSessionId, $chunkNum, $data)) {
            return new OperationResult(false, null, ['message' => 'Не удалось загрузить чанк']);
        }

        $completedChunksCount = $uploadSession->incrementCompletedChunks();
        $this->uploadSessionsRepo->updateCompletedChunks($uploadSessionId, $completedChunksCount);

        if ($uploadSession->isUploadComplete()) {
            return $this->finalizeUpload($uploadSession);
        }

        return new OperationResult(true, ['progress' => $uploadSession->getProgress()]);
    }

    public function cancelUploadSession(int $userId, int $uploadSessionId): OperationResult
    {
        $uploadSession = $this->uploadSessionsRepo->getById($userId, $uploadSessionId);
        if (!$uploadSession || $uploadSession->userId !== $userId) {
            return new OperationResult(false, null, ['message' => 'Сессия с таким айди не найдена']);
        }

        $this->uploadsStorage->deleteSessionDir($uploadSession->id);
        $this->uploadSessionsRepo->deleteSession($userId, $uploadSession->id);
        return new OperationResult(true);
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
            return new OperationResult(false, null, ['message' => 'Не удалось собрать файл']);
        }

        return new OperationResult(true, [
            'progress' => $uploadSession->getProgress(),
            'message' => 'Файл успешно загружен',
            'fileId' => $fileId,
            'parentDirId' => $uploadSession->destinationDirId
        ]);
    }

    private function buildFile(UploadSession $session): int|false
    {
        $buildResult = $this->fileBuilder->buildFile($session);
        if ($buildResult->success) {
            $this->uploadSessionsRepo->deleteSession($session->userId, $session->id);
            return $this->fsRepo->createFile(
                $session->userId,
                $session->fileName,
                $buildResult->filePath,
                $session->destinationDirId,
                $buildResult->fileSize
            );
        } else return false;
    }
}
