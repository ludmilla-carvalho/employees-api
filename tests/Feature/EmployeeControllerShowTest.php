<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmployeeControllerShowTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $otherUser;

    protected string $token;

    protected string $otherToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
        $this->otherToken = JWTAuth::fromUser($this->otherUser);
    }

    /**
     * Test showing an employee successfully
     */
    public function test_shows_employee_successfully()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'cpf' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'cpf',
                    'city',
                    'state',
                    'created_at',
                    'updated_at',
                ],
                'message',
            ])
            ->assertJson([
                'data' => [
                    'id' => $employee->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'cpf' => '11144477735',
                    'city' => 'São Paulo',
                    'state' => 'SP',
                ],
                'message' => 'Employee retrieved successfully',
            ]);
    }

    /**
     * Test that authentication is required to view an employee
     */
    public function test_requires_authentication()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test that a user cannot view another user's employee
     */
    public function test_prevents_user_from_viewing_other_users_employee()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->otherToken,
            'Accept' => 'application/json',
        ])->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);
    }

    /**
     * Test that a user can view their own employee
     */
    public function test_allows_user_to_view_own_employee()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'id' => $employee->id,
                    'user_id' => $this->user->id,
                ],
            ]);
    }

    /**
     * Test returning 404 for non-existent employee
     */
    public function test_returns_404_for_non_existent_employee()
    {
        $nonExistentId = 99999;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/employees/{$nonExistentId}");

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test showing employee with soft deleted check
     */
    public function test_shows_employee_with_soft_deleted_check()
    {
        $employee = Employee::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // First verify we can access the employee
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(Response::HTTP_OK);

        // Soft delete the employee
        $employee->delete();

        // Now verify we get 404 for soft deleted employee
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/employees/{$employee->id}");

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test handles authorization policy correctly
     */
    public function test_handles_authorization_policy_correctly()
    {
        // Create employees for both users
        $userEmployee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'User Employee',
        ]);

        $otherUserEmployee = Employee::factory()->create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other User Employee',
        ]);

        // User can access their own employee
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/employees/{$userEmployee->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'name' => 'User Employee',
                ],
            ]);

        // User cannot access other user's employee
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->getJson("/api/employees/{$otherUserEmployee->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
