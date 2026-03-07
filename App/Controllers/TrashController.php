<?php

namespace App\Controllers;

use App\Controllers\ControllerInterface;
use App\Http\Response;
use App\RequestValidators\TrashValidator;
use App\Services\FileSystemService;

class TrashController implements ControllerInterface
{
    public function __construct(
        private FileSystemService $fsService,
        private Response $response,
        private TrashValidator $requestValidator
    ) {}

    public function index()
    {
        $userId = auth()->getId();
        $res = $this->fsService->getDeletedFiles($userId);
        $this->response->sendJson($res);
    }

    public function restore()
    {
        $userId = auth()->getId();
        $ids = $this->requestValidator->restore();

        $res = $this->fsService->restoreFiles($userId, $ids);

        if ($res->success) {
            $this->response->sendJson($res->data);
        } else {
            $this->response->setStatusCode(400)->sendJson($res->errors);
        }
    }
}
