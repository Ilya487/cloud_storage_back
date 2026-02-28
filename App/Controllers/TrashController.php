<?php

namespace App\Controllers;

use App\Controllers\ControllerInterface;
use App\Http\Response;
use App\Services\FileSystemService;

class TrashController implements ControllerInterface
{
    public function __construct(
        private FileSystemService $fsService,
        private Response $response
    ) {}

    public function index()
    {
        $userId = auth()->getId();
        $res = $this->fsService->getDeletedFiles($userId);
        $this->response->sendJson($res);
    }
}
