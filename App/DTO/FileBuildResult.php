<?php

namespace App\DTO;

class FileBuildResult
{
    public function __construct(public readonly bool $success, public readonly ?string $filePath = null, public readonly ?int $fileSize = null) {}
}
