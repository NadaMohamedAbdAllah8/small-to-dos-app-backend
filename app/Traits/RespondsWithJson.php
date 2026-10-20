<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

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
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
