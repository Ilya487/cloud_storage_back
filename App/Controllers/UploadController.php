<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\AuthManager;
use App\Services\UploadService;

class UploadController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private UploadService $uploadService, private AuthManager $authManager) {}

    public function resolve(): void {}

    public function initUpload()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $fileName = $this->request->json()['fileName'];
        $fileSize = $this->request->json()['fileSize'];
        $destinationDirId = $this->request->json()['destinationDirId'] ?: null;

        $initResult = $this->uploadService->initializeUploadSession($userId, $fileName, $fileSize, $destinationDirId);
        if ($initResult->success) {
            $this->response->sendJson($initResult->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($initResult->errors);
        }
    }

    public function uploadChunk()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $uploadSessionId = $this->request->header('X-Session-Id');
        $chunkNum = $this->request->header('X-Chunk-Num');
        $data  = $this->request->body();

        $res = $this->uploadService->uploadChunk($userId, $uploadSessionId, $chunkNum, $data);
        if ($res->success) {
            $this->response->sendJson($res->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($res->errors);
        }
    }

    public function cancelUpload()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $uploadSessionId = $this->request->get('sessionId');

        $res = $this->uploadService->cancelUploadSession($userId, $uploadSessionId);
        if ($res->success) $this->response->setStatusCode(204)->send();
        else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function startBuild()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $uploadSessionId = $this->request->json()['sessionId'];

        $res = $this->uploadService->startBuild($userId, $uploadSessionId);
        if ($res->success) $this->response->setStatusCode(200)->sendJson($res->data);
        else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function checkStatus()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $uploadSessionId = $this->request->get('sessionId');

        $res = $this->uploadService->getSessionStatus($userId, $uploadSessionId);
        if ($res->success) $this->response->setStatusCode(200)->sendJson($res->data);
        else $this->response->setStatusCode(400)->sendJson($res->errors);
    }
}
