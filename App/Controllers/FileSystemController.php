<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\RequestValidators\FileSystemValidator;
use App\Services\AuthManager;
use App\Services\DownloadService;
use App\Services\FileSystemService;

class FileSystemController implements ControllerInterface
{
    public function __construct(
        private Request $request,
        private Response $response,
        private AuthManager $authManager,
        private FileSystemService $fsService,
        private DownloadService $downloadService,
        private FileSystemValidator $requestValidator
    ) {}

    public function resolve(): void {}

    public function create()
    {
        $data = $this->requestValidator->create();

        $userId = $this->authManager->getAuthUser()->getId();
        $dirName = trim($data['dirName']);
        $parentDirId = $data['parentDirId'] ?: null;

        $creationResult = $this->fsService->createFolder($userId, $dirName, $parentDirId);

        if ($creationResult->success) {
            $this->response->sendJson($creationResult->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($creationResult->errors);
        }
    }

    public function getFolderContent($dirId)
    {
        $verifiedDirId = $this->requestValidator->getContent($dirId);
        $userId = $this->authManager->getAuthUser()->getId();
        $result = $this->fsService->getFolderContent($userId, $verifiedDirId);

        if ($result->success) $this->response->sendJson($result->data);
        else $this->response->setStatusCode(400)->sendJson($result->errors);
    }

    public function renameObject($id)
    {
        $data = $this->requestValidator->rename($id);

        $objectId = $data['objectId'];
        $updatedDirName = trim($data['newName']);
        $userId = $this->authManager->getAuthUser()->getId();

        $renameRes = $this->fsService->renameObject($userId, $objectId, $updatedDirName);
        if ($renameRes->success) {
            $this->response->sendJson($renameRes->data);
        } else $this->response->setStatusCode(400)->sendJson($renameRes->errors);
    }

    public function delete()
    {
        $items = $this->requestValidator->delete();
        $userId = $this->authManager->getAuthUser()->getId();

        $deleteResult = $this->fsService->deleteObjects($userId, $items);

        if ($deleteResult->success) $this->response->setStatusCode(200)->sendJson($deleteResult->data);
        else $this->response->setStatusCode(400)->sendJson($deleteResult->errors);
    }

    public function move()
    {
        $data = $this->requestValidator->moveItems();
        $items = $data['items'];
        $toDirId = $data['toDirId'];
        $userId = $this->authManager->getAuthUser()->getId();

        $moveResult = $this->fsService->moveObjects($userId, $items, $toDirId);

        if ($moveResult->success) $this->response->setStatusCode(200)->sendJson($moveResult->data);
        else $this->response->setStatusCode(400)->sendJson($moveResult->errors);
    }

    public function getFolderIdByPath()
    {
        $path = $this->requestValidator->getFolderIdByPath();
        $userId = $this->authManager->getAuthUser()->getId();

        $res = $this->fsService->getDirIdByPath($userId, $path);
        if ($res->success) {
            $this->response->sendJson($res->data);
        } else {
            $this->response->setStatusCode(404)->sendJson($res->errors);
        }
    }

    public function search()
    {
        $query = $this->requestValidator->search();
        $userId = $this->authManager->getAuthUser()->getId();

        $res = $this->fsService->search($userId, $query);
        if ($res->success)
            $this->response->sendJson($res->data);
        else
            $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function getFileContent($fileId)
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $fileId = $this->requestValidator->getFileContent($fileId);

        $res = $this->downloadService->getFileServerPath($userId, $fileId);
        if ($res->success) {
            $this->response->outputFile($res->data['path']);
        } else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function getDiskInfo()
    {
        $user = $this->authManager->getAuthUser();

        $this->response->sendJson([
            'free' => $user->availableDiskSpace,
            'total' => $user->totalDiskSpace
        ]);
    }
}
