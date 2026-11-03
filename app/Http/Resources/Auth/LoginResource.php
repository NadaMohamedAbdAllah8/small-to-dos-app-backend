<?php

namespace App\Http\Resources\Auth;

use App\Auth\LoginResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoginResponseDTO
 */
class LoginResource extends JsonResource
{
    /**
     * @return array{token: string, token_type: string, user: array{id: int, name: string, email: string}}
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'token_type' => $this->tokenType,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
        ];
    }
}
