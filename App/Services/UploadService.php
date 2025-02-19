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

    public function __construct(
        private FileSystemRepository $fsRepo,
        private UploadSessionRepository $uploadSessionsRepo,
        private UploadsStorage $uploadsStorage,
        private FileAssembler $fileBuilder
    ) {}

    public function initializeUploadSession(int $userId, string $fileName, int $fileSize, ?int $destinationDirId): OperationResult
    {
        if (!$this->fsRepo->isNameAvailable($userId, $fileName, $destinationDirId)) {
            return new OperationResult(false, null, ['message' => 'Файл с таким именем уже существует!']);
        }

        $totalChunks = ceil($fileSize / self::CHUNK_SIZE);
        $uploadSessionId = $this->uploadSessionsRepo->createUploadSession($userId, $fileName, $totalChunks, $destinationDirId);
        if (!$this->uploadsStorage->initializeUploadDir($uploadSessionId)) {
            $this->uploadSessionsRepo->deleteSession($userId, $uploadSessionId);
            return new OperationResult(false, null, ['message' => 'Не удалась инициализировать сессию загрузки']);
        }

        return new OperationResult(true, ['sessionId' => $uploadSessionId, 'chunkSize' => self::CHUNK_SIZE, 'chunksCount' => $totalChunks]);
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
