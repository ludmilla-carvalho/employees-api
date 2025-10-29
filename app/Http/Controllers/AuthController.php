<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login user and return JWT token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return ApiResponse::success([
            'access_token' => $result['token'],
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // Convert minutes to seconds
            'user' => new UserResource($result['user']),
        ], 'Login successful');
    }

    /**
     * Get authenticated user information
     */
    public function me(): JsonResponse
    {
        $user = $this->authService->me();

        return ApiResponse::success(
            new UserResource($user),
            'User information retrieved successfully'
        );
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return ApiResponse::success(null, 'Logged out successfully');
    }

    /**
     * Refresh JWT token
     */
    public function refresh(): JsonResponse
    {
        $token = $this->authService->refresh();

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60, // Convert minutes to seconds
        ], 'Token refreshed successfully');
    }
}
