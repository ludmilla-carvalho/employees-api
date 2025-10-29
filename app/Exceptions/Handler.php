<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): JsonResponse|\Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response
    {
        // Handle API requests
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with consistent JSON responses
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            return ApiResponse::validationError(
                'Validation failed',
                $e->errors()
            );
        }

        // Handle model not found exceptions
        if ($e instanceof ModelNotFoundException) {
            return ApiResponse::notFound('Resource not found');
        }

        // Handle 404 exceptions
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::notFound('Endpoint not found');
        }

        // Handle HTTP exceptions
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $message = $e->getMessage() ?: 'An error occurred';

            return match ($statusCode) {
                Response::HTTP_UNAUTHORIZED => ApiResponse::unauthorized($message),
                Response::HTTP_FORBIDDEN => ApiResponse::forbidden($message),
                Response::HTTP_NOT_FOUND => ApiResponse::notFound($message),
                Response::HTTP_UNPROCESSABLE_ENTITY => ApiResponse::validationError($message),
                Response::HTTP_INTERNAL_SERVER_ERROR => ApiResponse::serverError($message),
                default => ApiResponse::error($message, $statusCode),
            };
        }

        // Handle custom exceptions
        if ($e instanceof AuthException) {
            return $e->render($request);
        }

        // Handle generic exceptions
        if (config('app.debug')) {
            return ApiResponse::serverError(
                $e->getMessage().' in '.$e->getFile().':'.$e->getLine()
            );
        }

        return ApiResponse::serverError('Internal server error');
    }
}
