<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\RequestValidators\DownloadValidator;
use App\Services\AuthManager;
use App\Services\DownloadService;

class DownloadController implements ControllerInterface
{
    public function __construct(
        private Request $request,
        private Response $response,
        private AuthManager $authManager,
        private DownloadService $downloadService,
        private DownloadValidator $requestValidator
    ) {}

    public function resolve(): void {}

    public function downloadFile($id)
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $validatedFileId = $this->requestValidator->downloadFile($id);

        $res = $this->downloadService->getFileServerPath($userId, $validatedFileId);
        if ($res->success) {
            $this->response->sendDownloadResponse($res->data['path']);
        } else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function iniArchive()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $items = $this->requestValidator->iniArchive();

        $res = $this->downloadService->iniArchive($userId, $items);
        if ($res->success) {
            $this->response->sendJson($res->data);
        } else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function checkArchiveStatus($id)
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $validateId = $this->requestValidator->checkArchiveStatus($id);

        $res = $this->downloadService->checkArchiveStatus($userId, $validateId);
        if ($res->success) {
            $this->response->sendJson($res->data);
        } else $this->response->sendJson($res->errors);
    }

    public function downloadArchive($id)
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $validateId = $this->requestValidator->downloadArchive($id);

        $res = $this->downloadService->getPathForArchiveDownlaod($userId, $validateId);
        if ($res->success) {
            $this->response->sendDownloadResponse($res->data['path']);
        } else $this->response->sendJson($res->errors);
    }
}
