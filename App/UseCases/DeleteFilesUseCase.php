<?php

namespace App\UseCases;

use App\Db\TransactionManager;
use App\DTO\OperationResult;
use App\Queue\Jobs\DeleteFilesJob;
use App\Queue\Queue;
use App\Repositories\FileSystemRepository;

class DeleteFilesUseCase
{
    public function __construct(
        private FileSystemRepository $fsRepo,
        private Queue $queue,
        private TransactionManager $txManager
    ) {}

    public function softDelete(int $userId, array $items): OperationResult
    {
        $failDelete = 0;
        $fsObjects = $this->fsRepo->getMany($userId, $items);
        if ($fsObjects === false) return OperationResult::createError(['message' => 'Не удалось удалить указанные объекты']);
        $failDelete += count($items) - $fsObjects->len();

        $failDelete += $this->txManager->withTransaction(function () use ($fsObjects) {
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

    public function deletePermanently(int $userId, array $ids)
    {
        $collection = $this->fsRepo->getFileTreeByIds($userId, $ids);
        if ($collection == false) return OperationResult::createError(['message' => 'Невозможно удалить указанные файлы']);

        $failDelete = $collection->len();
        $collection = $collection->filter(fn($fsObject) => $fsObject->inTrash);
        if ($collection->len() == 0) return OperationResult::createError(['message' => 'Невозможно удалить файл не из корзины']);
        $failDelete -=  $collection->len();

        $files = $collection->filesOnly();
        if ($files->len() == 0) {
            $this->fsRepo->deletePermanently($userId, $ids);
            return OperationResult::createSuccess([
                'successDelte' => count($ids) - $failDelete,
                'failDelete' => $failDelete
            ]);
        }

        $this->fsRepo->deletePermanently($userId, $collection->toIdsArray());
        $this->queue->push(DeleteFilesJob::create($collection->toIdsArray()));

        return OperationResult::createSuccess([
            'successDelte' => count($ids) - $failDelete,
            'failDelete' => $failDelete
        ]);
    }
}
