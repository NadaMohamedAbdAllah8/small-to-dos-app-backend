<?php

namespace App\Exceptions;

use App\Traits\RespondsWithJson;
use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationException extends Exception implements ShouldntReport
{
    use RespondsWithJson;

    public function render(): JsonResponse
    {
        return $this->returnErrorMessage(
            message: $this->getMessage() ?: 'Unauthenticated.',
            status: Response::HTTP_UNAUTHORIZED,
        );
    }
}
