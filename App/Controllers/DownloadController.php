<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\AuthManager;
use App\Services\FileSystemService;

class DownloadController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private AuthManager $authManager, private FileSystemService $fsService) {}

    public function resolve(): void
    {
        $userId = $this->authManager->getAuthUser()->getId();
        $fileId = $this->request->get('fileId');

        $res = $this->fsService->getPathForDownload($userId, $fileId);

        if (!$res->success) {
            $this->response->setStatusCode($res->errors['code'])->sendJson(['message' => $res->errors['message']]);
        }

        try {
            $this->sendResponseForDownload($res->data['path']);
        } finally {
            if ($res->data['type'] == 'folder') unlink($res->data['path']);
            $this->response->send();
        }
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

        $source = fopen($path, 'r');
        $output = fopen('php://output', 'w');
        stream_copy_to_stream($source, $output);
        fclose($source);
        fclose($output);
    }
}
