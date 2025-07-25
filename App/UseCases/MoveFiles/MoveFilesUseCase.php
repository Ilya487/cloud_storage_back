<?php

namespace App\UseCases\MoveFiles;

use App\Repositories\FileSystemRepository;
use App\Storage\DiskStorage;
use Error;

class MoveFilesUseCase
{
    public function __construct(private FileSystemRepository $fsRepo, private DiskStorage $diskStorage) {}

    public function __invoke(int $userId, array $items, ?int $toDirId = null): MoveFilesResult
    {
        $toDirPath = is_null($toDirId) ? '' : $this->fsRepo->getPathById($toDirId, $userId);
        if ($toDirPath === false) {
            return MoveFilesResult::createErrorResult('Указана некорректная папка назначения');
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

            if ($this->diskStorage->moveItem($userId, $currentPath, $toDirPath)) {
                try {
                    if ($type == 'folder') $this->fsRepo->moveFolder($userId, $currentPath, $updatedPath, $toDirId);
                    else $this->fsRepo->moveFile($userId, $currentPath, $updatedPath, $toDirId);
                    $succesMove++;
                } catch (Error $err) {
                    $this->fsRepo->moveFile($userId, $updatedPath, $currentPath);
                    throw $err;
                }
            } else
                $errorMove++;
        }

        if ($errorMove === count($items)) return MoveFilesResult::createErrorResult('Не удалось переместить объекты', $errorMove);
        else return MoveFilesResult::createSuccessResult($succesMove, $errorMove);
    }
}
