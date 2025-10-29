<?php

namespace Tests\Unit;

use App\Exceptions\AuthException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = new AuthService;
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);
    }

    /**
     * Test successful login
     */
    public function test_login_with_valid_credentials_returns_token_and_user(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $result = $this->authService->login($credentials);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertNotEmpty($result['token']);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertEquals($this->user->id, $result['user']->id);
        $this->assertEquals($this->user->email, $result['user']->email);
    }

    /**
     * Test login with invalid credentials throws exception
     */
    public function test_login_with_invalid_credentials_throws_exception(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Invalid credentials');
        $this->expectExceptionCode(Response::HTTP_UNAUTHORIZED);

        $this->authService->login($credentials);
    }

    /**
     * Test login with non-existent user throws exception
     */
    public function test_login_with_non_existent_user_throws_exception(): void
    {
        $credentials = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Invalid credentials');
        $this->expectExceptionCode(Response::HTTP_UNAUTHORIZED);

        $this->authService->login($credentials);
    }

    /**
     * Test me method returns authenticated user
     * Note: This test uses the acting as functionality to simulate authentication
     */
    public function test_me_returns_authenticated_user(): void
    {
        $this->actingAs($this->user, 'api');

        $result = $this->authService->me();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($this->user->id, $result->id);
        $this->assertEquals($this->user->email, $result->email);
    }

    /**
     * Test logout invalidates token
     */
    public function test_logout_invalidates_token(): void
    {
        $token = JWTAuth::fromUser($this->user);
        JWTAuth::setToken($token);

        $result = $this->authService->logout();

        $this->assertTrue($result);

        // Verify token is invalidated by trying to use it
        $this->expectException(\Tymon\JWTAuth\Exceptions\TokenInvalidException::class);
        JWTAuth::setToken($token)->authenticate();
    }

    /**
     * Test refresh returns new token
     */
    public function test_refresh_returns_new_token(): void
    {
        $originalToken = JWTAuth::fromUser($this->user);
        JWTAuth::setToken($originalToken);

        $newToken = $this->authService->refresh();

        $this->assertIsString($newToken);
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($originalToken, $newToken);

        // Verify new token works
        $user = JWTAuth::setToken($newToken)->authenticate();
        $this->assertEquals($this->user->id, $user->id);
    }

    /**
     * Test refresh with invalid token throws exception
     */
    public function test_refresh_with_invalid_token_throws_exception(): void
    {
        // Create a properly formatted but invalid JWT token
        $invalidToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpbnZhbGlkIjoidGVzdCJ9.invalid_signature';

        $this->app['request']->headers->set('Authorization', 'Bearer '.$invalidToken);
        JWTAuth::setRequest($this->app['request']);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Token refresh failed');
        $this->expectExceptionCode(Response::HTTP_UNAUTHORIZED);

        $this->authService->refresh();
    }

    /**
     * Test refresh with no token throws exception
     */
    public function test_refresh_with_no_token_throws_exception(): void
    {
        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Token refresh failed');
        $this->expectExceptionCode(Response::HTTP_UNAUTHORIZED);

        $this->authService->refresh();
    }
}
