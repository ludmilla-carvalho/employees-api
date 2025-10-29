<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmployeeControllerDestroyTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;

    private Employee $employee;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and authenticate
        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);

        // Create an employee for the user
        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Employee',
            'email' => 'test@example.com',
            'cpf' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
    }

    /**
     * Test deleting an employee successfully
     */
    public function test_deletes_employee_successfully()
    {
        Cache::shouldReceive('forget')->once()
            ->with("user:{$this->user->id}:employees");

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$this->employee->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'message' => 'Employee deleted successfully',
            ]);

        // Verify employee was soft deleted
        $this->assertSoftDeleted('employees', [
            'id' => $this->employee->id,
        ]);
    }

    /**
     * Test requires authentication
     */
    public function test_requires_authentication()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$this->employee->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        // Verify employee was not deleted
        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test prevents user from deleting other user's employee
     */
    public function test_prevents_user_from_deleting_other_users_employee()
    {
        // Create another user and their employee
        $otherUser = User::factory()->create();
        $otherEmployee = Employee::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$otherEmployee->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        // Verify employee was not deleted
        $this->assertDatabaseHas('employees', [
            'id' => $otherEmployee->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test allows user to delete own employee
     */
    public function test_allows_user_to_delete_own_employee()
    {
        Cache::shouldReceive('forget')->once()
            ->with("user:{$this->user->id}:employees");

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$this->employee->id}");

        $response->assertStatus(Response::HTTP_OK);

        // Verify employee was soft deleted
        $this->assertSoftDeleted('employees', [
            'id' => $this->employee->id,
        ]);
    }

    /**
     * Test returns 404 for non-existent employee
     */
    public function test_returns_404_for_non_existent_employee()
    {
        $nonExistentId = 99999;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$nonExistentId}");

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test returns 404 for already deleted employee
     */
    public function test_returns_404_for_already_deleted_employee()
    {
        // Soft delete the employee first
        $this->employee->delete();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$this->employee->id}");

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test clears cache after deleting employee
     */
    public function test_clears_cache_after_deleting_employee()
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with("user:{$this->user->id}:employees");

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$this->employee->id}");

        $response->assertStatus(Response::HTTP_OK);
    }

    /**
     * Test returns correct response structure
     */
    public function test_returns_correct_response_structure()
    {
        Cache::shouldReceive('forget')->once()
            ->with("user:{$this->user->id}:employees");

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$this->employee->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
            ])
            ->assertJson([
                'message' => 'Employee deleted successfully',
            ]);
    }

    /**
     * Test handles authorization policy correctly
     */
    public function test_handles_authorization_policy_correctly()
    {
        // Create another user with employee
        $anotherUser = User::factory()->create();
        $anotherEmployee = Employee::factory()->create([
            'user_id' => $anotherUser->id,
        ]);

        // Try to delete another user's employee
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$anotherEmployee->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);

        // Verify the employee was not deleted
        $this->assertDatabaseHas('employees', [
            'id' => $anotherEmployee->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * Test performs soft delete not hard delete
     */
    public function test_performs_soft_delete_not_hard_delete()
    {
        Cache::shouldReceive('forget')->once()
            ->with("user:{$this->user->id}:employees");

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$this->employee->id}");

        $response->assertStatus(Response::HTTP_OK);

        // Employee should still exist in database but with deleted_at timestamp
        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'email' => $this->employee->email,
        ]);

        // And should be soft deleted
        $this->assertSoftDeleted('employees', [
            'id' => $this->employee->id,
        ]);
    }

    public function test_works_with_invalid_bearer_token()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
            'Accept' => 'application/json',
        ])->deleteJson("/api/employees/{$this->employee->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);

        // Verify employee was not deleted
        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'deleted_at' => null,
        ]);
    }
}
