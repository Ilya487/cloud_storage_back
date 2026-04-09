<?php

namespace App\Scheduler;

use App\Repositories\RememberMeTokenRepository;

class DeleteExpiredAuthTokens
{
    public function __construct(private RememberMeTokenRepository $tokenRepo) {}

    public function handle()
    {
        $this->tokenRepo->deleteExpiredTokens();
    }
}
