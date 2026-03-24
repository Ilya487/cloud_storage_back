<?php

namespace App\Services;

use App\DTO\OperationResult;
use App\Exceptions\NotFoundException;
use App\Models\UploadSessionStatus;
use App\Queue\Jobs\BuildFileJob;
use App\Queue\Queue;
use App\Repositories\FileSystemRepository;
use App\Repositories\UploadSessionRepository;
use App\Repositories\UserRepository;
use App\Storage\UploadsStorage;

class UploadService
{
    private const CHUNK_SIZE = 5 * 1024 * 1024;
    private const MAX_ACTIVE_SESSION_FOR_USER = 5;
    private const SESSION_EXPIRE_INTERVAL = 3600;

    public function __construct(
        private FileSystemRepository $fsRepo,
        private UploadSessionRepository $uploadSessionsRepo,
        private UploadsStorage $uploadsStorage,
        private UserRepository $userRepo,
        private Queue $queue
    ) {}

    public function initializeUploadSession(int $userId, string $fileName, int $fileSize, ?int $destinationDirId): OperationResult
    {
        if ($this->uploadSessionsRepo->getUserSessionsCount($userId) == self::MAX_ACTIVE_SESSION_FOR_USER) {
            return OperationResult::createError(['message' => 'Вы превысили максимальное количество активных сессий']);
        }

        if ($destinationDirId !== null) {
            $destinationDirPath = $this->fsRepo->getPathById($destinationDirId, $userId);
            if ($destinationDirPath === false) {
                return OperationResult::createError(['message' => 'Папка назначения не существует или была удалена']);
            }
        } else $destinationDirPath = '/';

        $totalChunks = ceil($fileSize / self::CHUNK_SIZE);

        return $this->userRepo->withTransaction(function ($rollBack) use (
            $userId,
            $fileSize,
            $fileName,
            $totalChunks,
            $destinationDirPath,
            $destinationDirId
        ) {
            $canInsert = $this->userRepo->reserveDiskSpace($userId, $fileSize);
            if (!$canInsert) {
                $rollBack();
                return OperationResult::createError(['message' => 'Недостаточно свободного места на диске']);
            }

            $uploadSession = $this->uploadSessionsRepo->createUploadSession(
                $userId,
                $fileName,
                $totalChunks,
                $destinationDirPath,
                $destinationDirId,
                $fileSize,
                time() + self::SESSION_EXPIRE_INTERVAL
            );

            if (!$this->uploadsStorage->initializeUploadDir($uploadSession->id)) {
                $rollBack();
                return OperationResult::createError(['message' => 'Не удалась инициализировать сессию загрузки']);
            }

            return OperationResult::createSuccess([
                'sessionId' => (int)$uploadSession->id,
                'chunkSize' => self::CHUNK_SIZE,
                'chunksCount' => $totalChunks,
                'path' => $destinationDirPath
            ]);
        });
    }

    public function uploadChunk(int $userId, int $uploadSessionId, int $chunkNum, string $data): OperationResult
    {
        $uploadSession = $this->uploadSessionsRepo->getById($userId, $uploadSessionId);
        if ($uploadSession === false)  throw new NotFoundException('Сессия с данным айди не найдена');

        if ($uploadSession->isUploadComplete()) {
            return OperationResult::createError(['message' => 'Все чанки уже загружены']);
        }

        if (!$this->uploadsStorage->uploadChunk($uploadSessionId, $chunkNum, $data)) {
            return OperationResult::createError(['message' => 'Не удалось загрузить чанк']);
        }

        $count = $this->uploadSessionsRepo->incrementCompletedChunks($uploadSessionId);
        $uploadSession->setChunks($count);

        return OperationResult::createSuccess(['progress' => $uploadSession->getProgress()]);
    }

    public function cancelUploadSession(int $userId, int $uploadSessionId): OperationResult
    {
        $uploadSession = $this->uploadSessionsRepo->getById($userId, $uploadSessionId);
        if ($uploadSession === false) throw new NotFoundException('Сессия с данным айди не найдена');

        if (!$uploadSession->isUploading()) {
            return OperationResult::createError(['message' => 'Невозможно отменить сессию']);
        }

        $this->uploadsStorage->deleteSessionDir($uploadSession->id);
        $this->uploadSessionsRepo->setStatus($userId, $uploadSession->id, UploadSessionStatus::CANCELLED);
        $this->userRepo->freeUpDiskSpace($userId, $uploadSession->fileSize);
        return OperationResult::createSuccess([]);
    }

    // private function deleteExpiredSessions($userId)
    // {
    //     $sessions = $this->uploadSessionsRepo->getUserSessions($userId);
    //     foreach ($sessions as $session) {
    //         $timeDiff = time() - $session->lastUpdated->getTimestamp();
    //         if ($timeDiff >= self::SESSION_MAX_LIFETIME) {
    //             $this->cancelUploadSession($session->userId, $session->id);
    //         }
    //     }
    // }

    public function startBuild(int $userId, int $uploadSessionId): OperationResult
    {
        $uploadSession = $this->uploadSessionsRepo->getById($userId, $uploadSessionId);
        if ($uploadSession === false) throw new NotFoundException('Сессия с данным айди не найдена');

        if ($uploadSession->isBuilding()) return OperationResult::createError(['message' => 'Сборка сессии уже запущена']);
        if (!$uploadSession->canBeBuilded()) return OperationResult::createError(['message' => 'Невозможно запустить сборку']);

        $this->uploadSessionsRepo->setStatus($uploadSession->userId, $uploadSession->id, UploadSessionStatus::BUILDING);
        $this->queue->push(BuildFileJob::create($userId, $uploadSession->id));

        return OperationResult::createSuccess([
            'id' => $uploadSession->id,
            'status' => $uploadSession->status->value
        ]);
    }

    public function getSessionStatus(int $userId, int $sessionId): OperationResult
    {
        $session = $this->uploadSessionsRepo->getById($userId, $sessionId);
        if ($session === false) throw new NotFoundException('Сессия с данным айди не найдена');

        return OperationResult::createSuccess([
            'status' => $session->status->value
        ]);
    }
}
