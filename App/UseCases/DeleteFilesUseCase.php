<?php

namespace App\UseCases;

use App\DTO\OperationResult;
use App\Repositories\FileSystemRepository;
use App\Storage\DiskStorage;

class DeleteFilesUseCase
{
    public function __construct(
        private FileSystemRepository $fsRepo,
        private DiskStorage $diskStorage
    ) {}

    public function execute(int $userId, array $items): OperationResult
    {
        $failDelete = 0;
        $fsObjects = $this->fsRepo->getMany($userId, $items);
        if ($fsObjects === false) return OperationResult::createError(['message' => 'Не удалось удалить указанные объекты']);
        $failDelete += count($items) - count($fsObjects);

        foreach ($fsObjects as $fsObject) {
            $this->fsRepo->deleteObject($fsObject);
            if ($this->diskStorage->delete($userId, $fsObject->getPath())) {
                $this->fsRepo->confirmChanges();
            } else {
                $this->fsRepo->cancelLastChanges();
                $failDelete++;
            }
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
