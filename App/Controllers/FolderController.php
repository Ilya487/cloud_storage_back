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

            default:
                # code...
                break;
        }
    }

    private function create()
    {
        $data = $this->request->json();
        if (is_null($data)) {
            $this->response->setStatusCode(400)->sendJson(['message' => 'Неверный JSON']);
        }

        $userId = $this->authService->getAuthUser()->getId();
        $dirName = trim($data['dirName']);
        $parentDirId = $data['parentDirId'];

        $validationResult = (new FileSystemNameValidator($dirName))->validate();
        if (count($validationResult) !== 0) {
            $this->response->setStatusCode(400)->sendJson(['errors' => $validationResult]);
        }

        $creationResult = $this->fsService->createFolder($userId, $dirName, $parentDirId);
        if (is_null($creationResult)) $this->response->setStatusCode(500)->sendJson(['message' => 'An unexpected error occurred. Please try again later.']);

        if ($creationResult->success) {
            $this->response->sendJson($creationResult->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($creationResult->errors);
        }
    }
}
