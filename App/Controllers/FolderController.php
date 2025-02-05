<?php

namespace App\Controllers;

use App\Authentication\AuthenticationInterface;
use App\Http\Request;
use App\Http\Response;
use App\Controllers\ControllerInterface;
use App\Services\FileSystemService;
use App\Validators\FileSystemNameValidator;

class FolderController implements ControllerInterface
{
    public function __construct(private Request $request, private Response $response, private AuthenticationInterface $authService, private FileSystemService $fsService) {}

    public function resolve(): void
    {
        switch ($this->request->method) {
            case 'POST':
                $this->create();
                break;

            case 'GET':
                $this->getFolderContent();
                break;

            case 'PATCH':
                $this->renameFolder();
                break;
        }
    }

    private function create()
    {
        $data = $this->request->json();

        $userId = $this->authService->getAuthUser()->getId();
        $dirName = trim($data['dirName']);
        $parentDirId = $data['parentDirId'] ?: null;

        $validationResult = (new FileSystemNameValidator($dirName))->validate();
        if (count($validationResult) !== 0) {
            $this->response->setStatusCode(400)->sendJson(['errors' => $validationResult]);
        }

        $creationResult = $this->fsService->createFolder($userId, $dirName, $parentDirId);

        if ($creationResult->success) {
            $this->response->sendJson($creationResult->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($creationResult->errors);
        }
    }

    private function getFolderContent()
    {
        $userId = $this->authService->getAuthUser()->getId();
        $dirId = $this->request->get('dirId') ?: null;
        $result = $this->fsService->getFolderContent($userId, $dirId);

        if ($result->success) $this->response->sendJson($result->data);
        else $this->response->setStatusCode(400)->sendJson($result->errors);
    }

    private function renameFolder()
    {
        $data = $this->request->json();

        $dirId = $data['dirId'];
        $updatedDirName = trim($data['newName']);
        $userId = $this->authService->getAuthUser()->getId();

        $validationResult = (new FileSystemNameValidator($updatedDirName))->validate();
        if (count($validationResult) !== 0) {
            $this->response->setStatusCode(400)->sendJson(['errors' => $validationResult]);
        }

        $renameRes = $this->fsService->renameFolder($userId, $dirId, $updatedDirName);
        if ($renameRes->success) {
            $this->response->sendJson($renameRes->data);
        } else $this->response->setStatusCode(400)->sendJson($renameRes->errors);
    }
}
