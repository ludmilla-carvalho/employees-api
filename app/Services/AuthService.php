<?php

namespace App\Services;

use App\Exceptions\AuthException;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function login(array $credentials)
    {
        if (! $token = JWTAuth::attempt($credentials)) {
            throw new AuthException('Invalid credentials', Response::HTTP_UNAUTHORIZED);
        }

        $user = JWTAuth::user();

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    public function me()
    {
        return JWTAuth::user();
    }

    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return true;
    }

    public function refresh()
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());

            return $token;
        } catch (\Exception $e) {
            throw new AuthException('Token refresh failed', Response::HTTP_UNAUTHORIZED);
        }
    }
}
