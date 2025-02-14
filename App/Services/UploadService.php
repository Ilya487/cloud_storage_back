<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Repositories\FileSystemRepository;
use App\Repositories\UploadSessionRepository;
use App\Storage\UploadsStorage;

class UploadService
{
    private const CHUNK_SIZE = 5242880; //5mb

    public function __construct(private FileSystemRepository $fsRepo, private UploadSessionRepository $uploadSessionsRepo, private UploadsStorage $uploadsStorage) {}

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
}
