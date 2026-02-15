<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\AuthManager;
use App\Services\DownloadService;

class DownloadController implements ControllerInterface
{
    public function __construct(
        private Request $request,
        private Response $response,
        private AuthManager $authManager,
        private DownloadService $downloadService
    ) {}

    public function resolve(): void {}

    public function downloadFile()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $fileId = $this->request->get('fileId');

        $res = $this->downloadService->getFileServerPath($userId, $fileId);
        if ($res->success) {
            $this->response->sendDownloadResponse($res->data['path']);
        } else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function iniArchive()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $items = $this->request->json()['items'];

        $res = $this->downloadService->iniArchive($userId, $items);
        if ($res->success) {
            $this->response->sendJson($res->data);
        } else $this->response->setStatusCode(400)->sendJson($res->errors);
    }

    public function checkArchiveStatus()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $taskId = $this->request->get('taskId');

        $res = $this->downloadService->checkArchiveStatus($userId, $taskId);
        if ($res->success) {
            $this->response->sendJson($res->data);
        } else $this->response->sendJson($res->errors);
    }

    public function downloadArchive()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $taskId = $this->request->get('taskId');

        $res = $this->downloadService->getPathForArchiveDownlaod($userId, $taskId);
        if ($res->success) {
            $this->response->sendDownloadResponse($res->data['path']);
        } else $this->response->sendJson($res->errors);
    }
}
