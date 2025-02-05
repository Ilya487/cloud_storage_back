<?php

namespace App\Authentication;

use App\Models\User;

interface AuthenticationInterface
{
    public function auth(): bool;
    public function getAuthUser(): ?User;
    public function logOut(): bool;
    public function signIn(User $user): bool;
}
