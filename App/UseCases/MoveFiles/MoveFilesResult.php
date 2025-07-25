<?php

namespace App\UseCases\MoveFiles;

class MoveFilesResult
{
    private function __construct(
        public readonly bool $success,
        public readonly int $numSuccessMoves = 0,
        public readonly int $numErrorMoves = 0,
        public readonly string $errorMsg = ''
    ) {}

    public static function createSuccessResult(int $numSuccessMoves, int $numErrorMoves)
    {
        return new self(true, $numSuccessMoves, $numErrorMoves);
    }

    public static function createErrorResult(string $errorMsg, int $numErrorMoves = 0, int $numSuccessMoves = 0)
    {
        return new self(false, $numSuccessMoves, $numErrorMoves, $errorMsg);
    }
}
