<?php

namespace App\Controllers;

use App\Authentication\AuthenticationInterface;
use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\FileSystemService;

class DownloadController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private AuthenticationInterface $authService, private FileSystemService $fsService) {}

    public function resolve(): void
    {
        $userId = $this->authService->getAuthUser()->getId();
        $fileId = $this->request->get('fileId');

        $res = $this->fsService->getPathForDownload($userId, $fileId);

        if (!$res->success) {
            $this->response->setStatusCode($res->errors['code'])->sendJson(['message' => $res->errors['message']]);
        }

        $this->sendResponseForDownload($res->data['path']);
        if ($res->data['type'] == 'folder') unlink($res->data['path']);
    }

    private function sendResponseForDownload(string $path)
    {
        $baseName = rawurlencode(basename($path));
        $size = filesize($path);

        $this->response->setHeader('Content-Type', 'application/octet-stream');
        $this->response->setHeader(
            'Content-Disposition',
            "attachment; filename=$baseName"
        );
        $this->response->setHeader('Content-Length', $size);
        readfile($path);
    }
}
