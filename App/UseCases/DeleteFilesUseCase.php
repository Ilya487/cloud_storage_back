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

        foreach ($fsObjects as $fsObject) {
            $this->fsRepo->softDeleteObject($fsObject);
        }

        if ($failDelete == count($items)) return OperationResult::createError([
            'message' => "Не удалось удалить $failDelete шт."
        ]);
        else return OperationResult::createSuccess([
            'successDelete' => count($items) - $failDelete,
            'failDelete' => $failDelete,
        ]);
    }
}
