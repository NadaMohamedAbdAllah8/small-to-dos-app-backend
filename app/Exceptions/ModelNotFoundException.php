<?php

namespace App\Exceptions;

use App\Traits\RespondsWithJson;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ModelNotFoundException extends Exception
{
    use RespondsWithJson;

    public function render(): JsonResponse
    {
        return $this->returnErrorMessage(
            $this->getMessage() ?: 'Model does not exist',
            Response::HTTP_NOT_FOUND
        );
    }
}
