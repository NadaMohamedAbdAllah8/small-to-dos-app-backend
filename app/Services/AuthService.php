<?php

namespace App\Services;

use App\Auth\LoginDTO;
use App\Auth\LoginResponseDTO;
use App\Exceptions\AuthenticationException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(LoginDTO $credentials): LoginResponseDTO
    {
        $user = User::query()
            ->where('email', $credentials->email)
            ->first();

        if (! $user || ! Hash::check($credentials->password, $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        $token = $user->createToken('user-api-token')->plainTextToken;

        return new LoginResponseDTO(
            token: $token,
            tokenType: 'Bearer',
            user: $user,
        );
    }
}
