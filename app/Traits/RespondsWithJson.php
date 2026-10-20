<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait RespondsWithJson
{
    protected function returnErrorMessage(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
        ], $status);
    }

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
