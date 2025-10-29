<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponse
{
    /**
     * Success response
     */
    public static function success($data = null, string $message = 'Success', int $statusCode = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Error response
     */
    public static function error(string $message = 'Error', int $statusCode = Response::HTTP_BAD_REQUEST, $data = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Created response
     */
    public static function created($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Updated response
     */
    public static function updated($data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return self::success($data, $message, Response::HTTP_OK);
    }

    /**
     * Deleted response
     */
    public static function deleted(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return self::success(null, $message, Response::HTTP_OK);
    }

    /**
     * Not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Validation error response
     */
    public static function validationError(string $message = 'Validation failed', $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
