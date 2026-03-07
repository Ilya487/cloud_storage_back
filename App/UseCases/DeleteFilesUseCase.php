<?php

namespace App\UseCases;

use App\DTO\OperationResult;
use App\Repositories\FileSystemRepository;

class DeleteFilesUseCase
{
    public function __construct(
        private FileSystemRepository $fsRepo,
    ) {}

    public function softDelete(int $userId, array $items): OperationResult
    {
        $failDelete = 0;
        $fsObjects = $this->fsRepo->getMany($userId, $items);
        if ($fsObjects === false) return OperationResult::createError(['message' => 'Не удалось удалить указанные объекты']);
        $failDelete += count($items) - $fsObjects->len();

        $failDelete += $this->fsRepo->withTransaction(function () use ($fsObjects) {
            $failDelete = 0;
            foreach ($fsObjects as $fsObject) {
                if ($fsObject->inTrash) {
                    $failDelete++;
                    continue;
                }
                $this->fsRepo->softDeleteObject($fsObject);
            }
            return $failDelete;
        });

        if ($failDelete == count($items)) return OperationResult::createError([
            'message' => "Не удалось удалить $failDelete шт."
        ]);
        else return OperationResult::createSuccess([
            'successDelete' => count($items) - $failDelete,
            'failDelete' => $failDelete,
        ]);
    }
}
