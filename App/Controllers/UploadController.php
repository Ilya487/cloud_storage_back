<?php

namespace App\Controllers;

use App\Authentication\AuthenticationInterface;
use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\UploadService;

class UploadController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private UploadService $uploadService, private AuthenticationInterface $authService) {}

    public function resolve(): void {}

    public function initUpload()
    {
        $userId = $this->authService->getAuthUser()->getId();
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
}
