<?php

namespace App\UseCases;

use App\DTO\OperationResult;
use App\Exceptions\NotFoundException;
use App\Models\FileSystemObject;
use App\Repositories\FileSystemRepository;

class MoveFilesUseCase
{
    public function __construct(private FileSystemRepository $fsRepo) {}

    public function execute(int $userId, array $items, ?int $toDirId = null): OperationResult
    {
        $toDir = is_null($toDirId) ? FileSystemObject::createRootDir($userId) : $this->fsRepo->getById($userId, $toDirId);
        if ($toDir === false) {
            return OperationResult::createError(['message' => 'Указана некорректная папка назначения']);
        }

        $errorMove = 0;
        $succesMove = 0;

        $fsObjects = $this->fsRepo->getMany($userId, $items);
        if ($fsObjects === false) throw new NotFoundException('Указанные объекты не найдены');

        foreach ($fsObjects as $fsObject) {
            $res = $this->fsRepo->moveObject($fsObject, $toDir);
            if ($res === false) $errorMove++;
            else $succesMove++;
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
