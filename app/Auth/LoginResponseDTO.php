<?php

namespace App\Auth;

use App\Models\User;

final readonly class LoginResponseDTO
{
    public function __construct(
        public string $token,
        public string $tokenType,
        public User $user,
    ) {}
}
