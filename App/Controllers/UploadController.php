<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\RequestValidators\UploadValidator;
use App\Services\UploadService;

class UploadController implements ControllerInterface
{
    public function __construct(
        private Request $request,
        private Response $response,
        private UploadService $uploadService,
        private UploadValidator $requestValidator
    ) {}

    public function initUpload()
    {
        $userId = auth()->getId();
        $data = $this->requestValidator->initUpload();
        $fileName = $data['fileName'];
        $fileSize = $data['fileSize'];
        $destinationDirId = $data['destinationDirId'] ?: null;

        $initResult = $this->uploadService->initializeUploadSession($userId, $fileName, $fileSize, $destinationDirId);
        if ($initResult->success) {
            $this->response->sendJson($initResult->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($initResult->errors);
        }
    }

    public function uploadChunk($sessionId)
    {
        $userId = auth()->getId();
        $data = $this->requestValidator->uploadChunk($sessionId);
        $uploadSessionId = $data['sessionId'];
        $chunkNum = $data['chunkNum'];
        $chunkStream  = $this->request->getBodyAsResource();

        $res = $this->uploadService->uploadChunk($userId, $uploadSessionId, $chunkNum, $chunkStream);
        if ($res->success) {
            $this->response->sendJson($res->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($res->errors);
        }
    }

    public function cancelUpload($sessionId)
    {
        $userId = auth()->getId();
        $sessionId = $this->requestValidator->cancelUpload($sessionId);

        $res = $this->uploadService->cancelUploadSession($userId, $sessionId);
        if ($res->success) $this->response->setStatusCode(204)->send();
        else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function startBuild($sessionId)
    {
        $userId = auth()->getId();
        $sessionId = $this->requestValidator->startBuild($sessionId);

        $res = $this->uploadService->startBuild($userId, $sessionId);
        if ($res->success) $this->response->setStatusCode(200)->sendJson($res->data);
        else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function checkStatus($sessionId)
    {
        $userId = auth()->getId();
        $sessionId = $this->requestValidator->checkStatus($sessionId);

        $res = $this->uploadService->getSessionStatus($userId, $sessionId);
        if ($res->success) $this->response->setStatusCode(200)->sendJson($res->data);
        else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function getInfo()
    {
        $userId = auth()->getId();
        $ids = $this->requestValidator->getInfo();

        $res = $this->uploadService->getSessionsInfo($userId, $ids);

        if ($res->success) $this->response->setStatusCode(200)->sendJson($res->data);
        else $this->response->setStatusCode(400)->sendJson($res->errors);
    }
}
