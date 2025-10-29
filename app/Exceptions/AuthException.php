<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AuthException extends Exception
{
    public function render($request): JsonResponse
    {
        $statusCode = $this->getCode() ?: Response::HTTP_BAD_REQUEST;

        return match ($statusCode) {
            Response::HTTP_UNAUTHORIZED => ApiResponse::unauthorized($this->getMessage()),
            Response::HTTP_FORBIDDEN => ApiResponse::forbidden($this->getMessage()),
            default => ApiResponse::error($this->getMessage(), $statusCode),
        };
    }
}
