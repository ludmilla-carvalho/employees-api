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

class EmployeeControllerStoreTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    /**
     * Test successful employee creation
     */
    public function test_creates_employee_successfully()
    {
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('forget')->once();

        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '11144477735', // CPF válido
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_CREATED)
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
                    'name' => $employeeData['name'],
                    'email' => $employeeData['email'],
                    'cpf' => $employeeData['cpf'],
                    'city' => $employeeData['city'],
                    'state' => $employeeData['state'],
                ],
                'message' => 'Employee created successfully',
            ]);

        $this->assertDatabaseHas('employees', [
            'user_id' => $this->user->id,
            'name' => $employeeData['name'],
            'email' => $employeeData['email'],
            'cpf' => $employeeData['cpf'],
            'city' => $employeeData['city'],
            'state' => $employeeData['state'],
        ]);
    }

    /**
     * Test creating employee with state abbreviation
     */
    public function test_creates_employee_with_state_abbreviation()
    {
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('forget')->once();

        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '52998224725', // CPF válido
            'city' => $this->faker->city,
            'state' => 'RJ',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'data' => [
                    'state' => 'RJ',
                ],
            ]);
    }

    /**
     * Test creating employee with state full name
     */
    public function test_creates_employee_with_state_full_name()
    {
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('forget')->once();

        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '52998224725', // CPF válido
            'city' => $this->faker->city,
            'state' => 'São Paulo',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'data' => [
                    'state' => 'SP',
                ],
            ]);
    }

    /**
     * Test requires authentication
     */
    public function test_requires_authentication()
    {
        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '11144477735',
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED)
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    /**
     * Test validates required fields
     */
    public function test_validates_required_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'cpf',
                'city',
                'state',
            ]);
    }

    /**
     * Test validates email format
     */
    public function test_validates_email_format()
    {
        $employeeData = [
            'name' => $this->faker->name,
            'email' => 'invalid-email',
            'cpf' => '11144477735',
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test validates email uniqueness
     */
    public function test_validates_email_uniqueness()
    {
        $existingEmployee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'existing@example.com',
        ]);

        $employeeData = [
            'name' => $this->faker->name,
            'email' => 'existing@example.com',
            'cpf' => '11144477735',
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test validates CPF format
     */
    public function test_validates_cpf_format()
    {
        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => 'invalid-cpf',
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['cpf']);
    }

    /**
     * Test validates CPF size
     */
    public function test_validates_cpf_size()
    {
        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '123', // Muito pequeno
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['cpf']);
    }

    /**
     * Test validates CPF uniqueness
     */
    public function test_validates_cpf_uniqueness()
    {
        $existingEmployee = Employee::factory()->create([
            'user_id' => $this->user->id,
            'cpf' => '11144477735',
        ]);

        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '11144477735',
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['cpf']);
    }

    /**
     * Test validates city field
     */
    public function test_validates_city_field()
    {
        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '11144477735',
            'city' => str_repeat('a', 256), // Excede o limite de 255 caracteres
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['city']);
    }

    /**
     * Test validates state field
     */
    public function test_validates_state_field()
    {
        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '11144477735',
            'city' => $this->faker->city,
            'state' => 'INVALID_STATE',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['state']);
    }

    /**
     * Test clears cache after creating employee
     */
    public function test_clears_cache_after_creating_employee()
    {
        $cacheKey = "user:{$this->user->id}:employees";

        // Mock Cache facade corretamente
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('forget')->with($cacheKey)->once();

        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '11144477735',
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    /**
     * Test associates employee with authenticated user
     */
    public function test_associates_employee_with_authenticated_user()
    {
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('forget')->once();

        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '11144477735',
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('employees', [
            'user_id' => $this->user->id,
            'name' => $employeeData['name'],
        ]);
    }

    /**
     * Test accepts CPF with formatted CPF
     */
    public function test_accepts_cpf_with_formatted_cpf()
    {
        Cache::shouldReceive('store')->with('redis')->andReturnSelf();
        Cache::shouldReceive('forget')->once();

        $employeeData = [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'cpf' => '111.444.777-35', // CPF formatado válido
            'city' => $this->faker->city,
            'state' => 'SP',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees', $employeeData);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJson([
                'data' => [
                    'cpf' => '11144477735', // CPF normalizado no banco
                ],
            ]);
    }
}
