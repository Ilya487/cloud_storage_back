<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Exceptions\NotFoundException;
use App\Services\AuthManager;
use App\Services\DownloadService;
use App\Services\FileSystemService;

class DownloadController implements ControllerInterface
{
    public function __construct(
        private Request $request,
        private Response $response,
        private AuthManager $authManager,
        private FileSystemService $fsService,
        private DownloadService $downloadService
    ) {}

    public function resolve(): void
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $items = $this->request->get('items');

        try {
            $res = $this->fsService->getPathForDownload($userId, $items);
            if (!$res->success) {
                $this->response->setStatusCode(400)->sendJson(['message' => $res->errors['message']]);
            }

            $this->response->sendDownloadResponse($res->data['path']);
        } catch (NotFoundException $err) {
            $this->response->setStatusCode(404)->sendJson(['message' => $err->getMessage()]);
        }
    }

    public function downloadFile()
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $fileId = $this->request->get('fileId');

        $res = $this->downloadService->getPathForFileDownload($userId, $fileId);
        if ($res->success) {
            $this->response->sendDownloadResponse($res->data['path']);
        } else $this->response->setStatusCode(400)->sendJson($res->errors);
    }
}
