<?php

namespace App\Controllers;

use App\Authentication\AuthenticationInterface;
use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\FileSystemService;

class FolderController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private AuthenticationInterface $authService, private FileSystemService $fsService) {}

    public function resolve(): void {}

    public function create()
    {
        $data = $this->request->json();

        $userId = $this->authService->getAuthUser()->getId();
        $dirName = trim($data['dirName']);
        $parentDirId = $data['parentDirId'] ?: null;

        $creationResult = $this->fsService->createFolder($userId, $dirName, $parentDirId);

        if ($creationResult->success) {
            $this->response->sendJson($creationResult->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($creationResult->errors);
        }
    }

    public function getFolderContent()
    {
        $userId = $this->authService->getAuthUser()->getId();
        $dirId = $this->request->get('dirId') ?: null;
        $result = $this->fsService->getFolderContent($userId, $dirId);

        if ($result->success) $this->response->sendJson($result->data);
        else $this->response->setStatusCode(400)->sendJson($result->errors);
    }

    public function renameObject()
    {
        $data = $this->request->json();

        $objectId = $data['objectId'];
        $updatedDirName = trim($data['newName']);
        $userId = $this->authService->getAuthUser()->getId();

        $renameRes = $this->fsService->renameObject($userId, $objectId, $updatedDirName);
        if ($renameRes->success) {
            $this->response->sendJson($renameRes->data);
        } else $this->response->setStatusCode(400)->sendJson($renameRes->errors);
    }

    public function delete()
    {
        $objectId = $this->request->get('objectId');
        $userId = $this->authService->getAuthUser()->getId();

        $deleteResult = $this->fsService->deleteObject($userId, $objectId);

        if ($deleteResult->success) $this->response->setStatusCode(200)->sendJson($deleteResult->data);
        else $this->response->setStatusCode(400)->sendJson($deleteResult->errors);
    }

    public function move()
    {
        $objectId = $this->request->json()['itemId'];
        $toDirId = $this->request->json()['toDirId'] ?: null;
        $userId = $this->authService->getAuthUser()->getId();

        $moveResult = $this->fsService->moveObject($userId, $objectId, $toDirId);

        if ($moveResult->success) $this->response->setStatusCode(200)->sendJson($moveResult->data);
        else $this->response->setStatusCode(400)->sendJson($moveResult->errors);
    }

    public function getFolderIdByPath()
    {
        $path = $this->request->get('path');
        $userId = $this->authService->getAuthUser()->getId();

        $res = $this->fsService->getDirIdByPath($userId, $path);
        if ($res->success) {
            $this->response->sendJson($res->data);
        } else {
            $this->response->setStatusCode(404)->sendJson($res->errors);
        }
    }
}
