<?php

namespace App\UseCases;

use App\DTO\OperationResult;
use App\Repositories\FileSystemRepository;
use App\Storage\DiskStorage;

class MoveFilesUseCase
{
    public function __construct(private FileSystemRepository $fsRepo, private DiskStorage $diskStorage) {}

    public function execute(int $userId, array $items, ?int $toDirId = null): OperationResult
    {
        $toDirPath = is_null($toDirId) ? '/' : $this->fsRepo->getPathById($toDirId, $userId);
        if ($toDirPath === false) {
            return OperationResult::createError(['message' => 'Указана некорректная папка назначения']);
        }

        $errorMove = 0;
        $succesMove = 0;

        foreach ($items as $objectId) {
            $fsObject = $this->fsRepo->getById($userId, $objectId);

            if ($fsObject === false) {
                $errorMove++;
                continue;
            }

            $currentPath = $fsObject->getPath();
            $updatedPath = $fsObject->changeDir($toDirId, $toDirPath);
            if ($updatedPath === false) {
                $errorMove++;
                continue;
            }

            $this->fsRepo->moveObject($fsObject->ownerId, $fsObject->type, $currentPath, $updatedPath, $toDirId);

            if ($this->diskStorage->moveItem($userId, $currentPath, $toDirPath)) {
                $this->fsRepo->confirmChanges();
                $succesMove++;
            } else {
                $this->fsRepo->cancelLastChanges();
                $errorMove++;
            }
        }

        if ($errorMove === count($items)) return OperationResult::createError(
            ['message' => 'Не удалось переместить объекты', 'errorMoves' => $errorMove]
        );
        else return OperationResult::createSuccess([
            'successMoves' => $succesMove,
            'errorMoves' => $errorMove
        ]);
    }
}
