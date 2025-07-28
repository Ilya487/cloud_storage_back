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
        $toDirPath = is_null($toDirId) ? '' : $this->fsRepo->getPathById($toDirId, $userId);
        if ($toDirPath === false) {
            return OperationResult::createError(['message' => 'Указана некорректная папка назначения']);
        }

        $errorMove = 0;
        $succesMove = 0;

        foreach ($items as $objectId) {
            $type = $this->fsRepo->getTypeById($userId, $objectId);
            if ($type === false) {
                $errorMove++;
                continue;
            }

            if ($objectId == $toDirId) {
                $errorMove++;
                continue;
            }

            $currentPath = $this->fsRepo->getPathById($objectId, $userId);

            $updatedPath = "$toDirPath/" . basename($currentPath);

            if ($currentPath == $updatedPath) {
                $errorMove++;
                continue;
            }

            if ($type == 'folder') $this->fsRepo->moveFolder($userId, $currentPath, $updatedPath, $toDirId);
            else $this->fsRepo->moveFile($userId, $currentPath, $updatedPath, $toDirId);

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
