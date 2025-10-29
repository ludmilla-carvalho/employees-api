<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EmployeeControllerIndexTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Clear cache before each test
        Cache::flush();
    }

    /**
     * Test authenticated user can get their employees when they have none
     */
    public function test_authenticated_user_can_get_empty_employee_list(): void
    {
        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/employees');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'data',
            ])
            ->assertJson([
                'message' => 'Employees retrieved successfully',
                'data' => [],
            ]);
    }

    /**
     * Test authenticated user can get their employees when they have some
     */
    public function test_authenticated_user_can_get_their_employees(): void
    {
        // Create employees for the authenticated user
        $employees = Employee::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/employees');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'cpf',
                        'city',
                        'state',
                        'user_id',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ])
            ->assertJson([
                'message' => 'Employees retrieved successfully',
            ])
            ->assertJsonCount(3, 'data');

        // Verify that all returned employees belong to the authenticated user
        $responseData = $response->json('data');
        foreach ($responseData as $employee) {
            $this->assertEquals($this->user->id, $employee['user_id']);
        }

        // Verify specific employee data
        $firstEmployee = $employees->first();
        $response->assertJsonFragment([
            'id' => $firstEmployee->id,
            'name' => $firstEmployee->name,
            'email' => $firstEmployee->email,
            'cpf' => $firstEmployee->cpf,
            'city' => $firstEmployee->city,
            'state' => $firstEmployee->state->value,
            'user_id' => $firstEmployee->user_id,
        ]);
    }

    /**
     * Test authenticated user only sees their own employees, not others
     */
    public function test_authenticated_user_only_sees_their_own_employees(): void
    {
        // Create employees for the authenticated user
        $userEmployees = Employee::factory(2)->create(['user_id' => $this->user->id]);

        // Create employees for another user
        $otherEmployees = Employee::factory(3)->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/employees');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data');

        // Verify only user's employees are returned
        $responseData = $response->json('data');
        $returnedIds = collect($responseData)->pluck('id')->toArray();
        $userEmployeeIds = $userEmployees->pluck('id')->toArray();
        $otherEmployeeIds = $otherEmployees->pluck('id')->toArray();

        // Should contain user's employees
        foreach ($userEmployeeIds as $id) {
            $this->assertContains($id, $returnedIds);
        }

        // Should NOT contain other user's employees
        foreach ($otherEmployeeIds as $id) {
            $this->assertNotContains($id, $returnedIds);
        }
    }

    /**
     * Test unauthenticated user cannot access employees list
     */
    public function test_unauthenticated_user_cannot_access_employees_list(): void
    {
        Employee::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/employees');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test authenticated user with invalid token cannot access employees list
     */
    public function test_user_with_invalid_token_cannot_access_employees_list(): void
    {
        Employee::factory(3)->create(['user_id' => $this->user->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer invalid-token',
        ])->getJson('/api/employees');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test employees list performance with many employees
     */
    public function test_employees_list_with_many_employees(): void
    {
        // Create a larger number of employees
        Employee::factory(50)->create(['user_id' => $this->user->id]);

        $startTime = microtime(true);

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/employees');

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(50, 'data');

        // Ensure response time is reasonable (less than 2 seconds)
        $this->assertLessThan(2, $executionTime, 'Employees list should load in less than 2 seconds');
    }

    /**
     * Test employees list caching behavior
     */
    public function test_employees_list_uses_caching(): void
    {
        Employee::factory(3)->create(['user_id' => $this->user->id]);

        // First request should cache the result
        $firstResponse = $this->actingAs($this->user, 'api')
            ->getJson('/api/employees');

        $firstResponse->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data');

        // Verify cache key exists
        $cacheKey = "user:{$this->user->id}:employees";
        $this->assertTrue(Cache::has($cacheKey));

        // Second request should use cached result
        $secondResponse = $this->actingAs($this->user, 'api')
            ->getJson('/api/employees');

        $secondResponse->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data');

        // Both responses should be identical
        $this->assertEquals(
            $firstResponse->json('data'),
            $secondResponse->json('data')
        );
    }

    /**
     * Test employees list with soft deleted employees (should not appear)
     */
    public function test_employees_list_excludes_soft_deleted_employees(): void
    {
        $activeEmployee = Employee::factory()->create(['user_id' => $this->user->id]);
        $deletedEmployee = Employee::factory()->create(['user_id' => $this->user->id]);

        // Soft delete one employee
        $deletedEmployee->delete();

        $response = $this->actingAs($this->user, 'api')
            ->getJson('/api/employees');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');

        $responseData = $response->json('data');
        $returnedIds = collect($responseData)->pluck('id')->toArray();

        // Should contain active employee
        $this->assertContains($activeEmployee->id, $returnedIds);

        // Should NOT contain deleted employee
        $this->assertNotContains($deletedEmployee->id, $returnedIds);
    }
}
