<?php

namespace App\Http\Controllers\Api;

use App\Auth\LoginDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Services\AuthService;
use App\Traits\RespondsWithJson;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use RespondsWithJson;

    public function __construct(private readonly AuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $loginDto = LoginDTO::from($request->validated());

        $data = $this->authService->login(credentials: $loginDto);

        return $this->respondWithJson(
            data: new LoginResource($data),
            message: 'Logged in successfully',
        );
    }
}
