<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class EmployeeException extends Exception
{
    public function render($request): JsonResponse
    {
        $statusCode = $this->getCode() ?: Response::HTTP_BAD_REQUEST;

        return match ($statusCode) {
            Response::HTTP_NOT_FOUND => ApiResponse::notFound($this->getMessage()),
            Response::HTTP_UNPROCESSABLE_ENTITY => ApiResponse::validationError($this->getMessage()),
            Response::HTTP_CONFLICT => ApiResponse::error($this->getMessage(), Response::HTTP_CONFLICT),
            default => ApiResponse::error($this->getMessage(), $statusCode),
        };
    }
}
