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

class EmployeeControllerUpdateTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected User $otherUser;

    protected string $token;

    protected string $otherToken;

    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
        $this->otherToken = JWTAuth::fromUser($this->otherUser);

        $this->employee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'cpf' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
    }

    public function test_updates_employee_successfully()
    {
        Cache::shouldReceive('forget')->once();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'cpf' => '52998224725',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

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
                    'id' => $this->employee->id,
                    'name' => 'Updated Name',
                    'email' => 'updated@example.com',
                    'cpf' => '52998224725',
                    'city' => 'Rio de Janeiro',
                    'state' => 'RJ',
                ],
                'message' => 'Employee updated successfully',
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'cpf' => '52998224725',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
        ]);
    }

    /**
     * Test updating an employee partially
     */
    public function test_updates_employee_partially()
    {
        Cache::shouldReceive('forget')->once();

        $updateData = [
            'name' => 'Partially Updated Name',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'id' => $this->employee->id,
                    'name' => 'Partially Updated Name',
                    'email' => 'original@example.com', // Should remain unchanged
                    'cpf' => '11144477735', // Should remain unchanged
                    'city' => 'São Paulo', // Should remain unchanged
                    'state' => 'SP', // Should remain unchanged
                ],
            ]);
    }

    /**
     * Test that authentication is required to update an employee
     */
    public function test_requires_authentication()
    {
        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test that a user cannot update another user's employee
     */
    public function test_prevents_user_from_updating_other_users_employee()
    {
        $updateData = [
            'name' => 'Unauthorized Update',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->otherToken,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_FORBIDDEN)
            ->assertJson([
                'message' => 'This action is unauthorized.',
            ]);

        // Verify original data is unchanged
        $this->assertDatabaseHas('employees', [
            'id' => $this->employee->id,
            'name' => 'Original Name',
        ]);
    }

    /**
     * Test updating a non-existent employee returns 404
     */
    public function test_returns_404_for_non_existent_employee()
    {
        $updateData = [
            'name' => 'Updated Name',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson('/api/employees/99999', $updateData);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * Test validates_email_format
     */
    public function test_validates_email_uniqueness()
    {
        $otherEmployee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'other@example.com',
        ]);

        $updateData = [
            'email' => 'other@example.com', // Already exists
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test allows keeping same email
     */
    public function test_allows_keeping_same_email()
    {
        Cache::shouldReceive('forget')->once();

        $updateData = [
            'name' => 'Updated Name',
            'email' => 'original@example.com', // Same email as current
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'email' => 'original@example.com',
                ],
            ]);
    }

    /**
     * Test validates CPF size
     */
    public function test_validates_cpf_size()
    {
        $updateData = [
            'cpf' => '123', // Too short
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['cpf']);
    }

    /**
     * Test validates CPF uniqueness
     */
    public function test_validates_cpf_uniqueness()
    {
        $otherEmployee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'cpf' => '98765432100',
        ]);

        $updateData = [
            'cpf' => '98765432100', // Already exists
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['cpf']);
    }

    /**
     * Test allows keeping same CPF
     */
    public function test_allows_keeping_same_cpf()
    {
        Cache::shouldReceive('forget')->once();

        $updateData = [
            'name' => 'Updated Name',
            'cpf' => '11144477735', // Same CPF as current
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'name' => 'Updated Name',
                    'cpf' => '11144477735',
                ],
            ]);
    }

    /**
     * Test validates state field
     */
    public function test_validates_state_field()
    {
        $updateData = [
            'state' => 'INVALID_STATE',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['state']);
    }

    public function test_updates_employee_with_state_full_name()
    {
        Cache::shouldReceive('forget')->once();

        $updateData = [
            'state' => 'Rio de Janeiro', // Full state name
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'state' => 'RJ', // Should be converted to abbreviation
                ],
            ]);
    }

    /**
     * Test updates employee with formatted CPF
     */
    public function test_updates_employee_with_formatted_cpf()
    {
        Cache::shouldReceive('forget')->once();

        $updateData = [
            'cpf' => '529.982.247-25', // Formatted CPF
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'cpf' => '52998224725', // Should be stored without formatting
                ],
            ]);
    }

    /**
     * Test clears cache after updating employee
     */
    public function test_clears_cache_after_updating_employee()
    {
        $cacheKey = "user:{$this->user->id}:employees";

        // Mock Cache facade
        Cache::shouldReceive('forget')->with($cacheKey)->once();

        $updateData = [
            'name' => 'Cache Test Update',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK);
    }

    /**
     * Test handles PATCH request
     */
    public function test_handles_patch_request()
    {
        Cache::shouldReceive('forget')->once();

        $updateData = [
            'name' => 'PATCH Updated Name',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->patchJson("/api/employees/{$this->employee->id}", $updateData);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'name' => 'PATCH Updated Name',
                ],
            ]);
    }

    /**
     * Test handles empty update data
     */
    public function test_handles_empty_update_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->putJson("/api/employees/{$this->employee->id}", []);

        $response->assertStatus(Response::HTTP_OK)
            ->assertJson([
                'data' => [
                    'name' => 'Original Name', // Should remain unchanged
                    'email' => 'original@example.com',
                ],
            ]);
    }

    /**
     * Test updates with valid Brazilian states
     */
    public function test_updates_with_valid_brazilian_states()
    {
        Cache::shouldReceive('forget')->times(8);

        $states = [
            'SP' => 'SP',
            'RJ' => 'RJ',
            'São Paulo' => 'SP',
            'Rio de Janeiro' => 'RJ',
            'Minas Gerais' => 'MG',
            'Rio Grande do Sul' => 'RS',
            'Paraná' => 'PR',
            'Santa Catarina' => 'SC',
        ];

        foreach ($states as $inputState => $expectedState) {
            $updateData = [
                'state' => $inputState,
            ];

            $response = $this->withHeaders([
                'Authorization' => 'Bearer '.$this->token,
                'Accept' => 'application/json',
            ])->putJson("/api/employees/{$this->employee->id}", $updateData);

            $response->assertStatus(Response::HTTP_OK)
                ->assertJson([
                    'data' => [
                        'state' => $expectedState,
                    ],
                ]);
        }
    }
}
