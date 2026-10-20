<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait RespondsWithJson
{
    protected function respondWithJson(
        mixed $data,
        string $message,
        int $status,
    ): JsonResponse {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
