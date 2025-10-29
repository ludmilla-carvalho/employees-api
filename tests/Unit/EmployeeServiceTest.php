<?php

namespace Tests\Unit;

use App\Models\Employee;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use App\Repositories\UserRepository;
use App\Services\EmployeeService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected EmployeeService $employeeService;

    protected $employeeRepository;

    protected $userRepository;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeRepository = Mockery::mock(EmployeeRepository::class);
        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->employeeService = new EmployeeService($this->employeeRepository, $this->userRepository);

        $this->user = User::factory()->make(['id' => 1, 'email' => 'test@example.com']);

        // Mock facades
        Cache::spy();
        Mail::fake();
        Storage::fake('local');
    }

    #[Test]
    public function it_gets_user_employees_from_cache()
    {
        $userId = 1;
        $employees = new Collection([
            Employee::factory()->make(['id' => 1]),
            Employee::factory()->make(['id' => 2]),
        ]);

        Auth::shouldReceive('id')->andReturn($userId);
        Cache::shouldReceive('remember')
            ->once()
            ->with("user:{$userId}:employees", config('employees.cache_ttl'), \Mockery::type('callable'))
            ->andReturn($employees);

        $result = $this->employeeService->getUserEmployees();

        $this->assertEquals($employees, $result);
    }

    #[Test]
    public function it_creates_employee_successfully()
    {
        $userId = 1;
        $data = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'cpf' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];

        $employee = Employee::factory()->make(array_merge($data, ['user_id' => $userId]));

        Auth::shouldReceive('id')->andReturn($userId);

        $this->employeeRepository
            ->shouldReceive('create')
            ->once()
            ->with(array_merge($data, ['user_id' => $userId]))
            ->andReturn($employee);

        Cache::shouldReceive('store')
            ->once()
            ->with('redis')
            ->andReturnSelf();

        Cache::shouldReceive('forget')
            ->once()
            ->with("user:{$userId}:employees");

        $result = $this->employeeService->createEmployee($data);

        $this->assertEquals($employee, $result);
    }

    #[Test]
    public function it_creates_employee_for_specific_user()
    {
        $userId = 2;
        $data = [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'cpf' => '52998224725',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
        ];

        $employee = Employee::factory()->make(array_merge($data, ['user_id' => $userId]));

        $this->employeeRepository
            ->shouldReceive('create')
            ->once()
            ->with(array_merge($data, ['user_id' => $userId]))
            ->andReturn($employee);

        Cache::shouldReceive('forget')
            ->once()
            ->with("user:{$userId}:employees");

        $result = $this->employeeService->createEmployeeForUser($userId, $data);

        $this->assertEquals($employee, $result);
    }

    #[Test]
    public function it_updates_employee_and_clears_cache()
    {
        $employee = Employee::factory()->make(['user_id' => 1]);
        $data = ['name' => 'Updated Name'];

        $this->employeeRepository
            ->shouldReceive('update')
            ->once()
            ->with($employee, $data);

        Cache::shouldReceive('forget')
            ->once()
            ->with('user:1:employees');

        $result = $this->employeeService->updateEmployee($employee, $data);

        $this->assertEquals($employee, $result);
    }

    #[Test]
    public function it_deletes_employee_and_clears_cache()
    {
        $employee = Employee::factory()->make(['user_id' => 1]);

        $this->employeeRepository
            ->shouldReceive('delete')
            ->once()
            ->with($employee)
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->once()
            ->with('user:1:employees');

        $result = $this->employeeService->deleteEmployee($employee);

        $this->assertTrue($result);
    }

    #[Test]
    public function it_does_not_clear_cache_when_delete_fails()
    {
        $employee = Employee::factory()->make(['user_id' => 1]);

        $this->employeeRepository
            ->shouldReceive('delete')
            ->once()
            ->with($employee)
            ->andReturn(false);

        Cache::shouldNotReceive('forget');

        $result = $this->employeeService->deleteEmployee($employee);

        $this->assertFalse($result);
    }

    #[Test]
    public function it_processes_csv_import_successfully()
    {
        $userId = 1;
        $filePath = 'imports/test.csv';
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@example.com,11144477735,São Paulo,SP\n";
        $csvContent .= 'Maria Santos,maria@example.com,52998224725,Rio de Janeiro,RJ';

        Storage::disk('local')->put($filePath, $csvContent);

        $this->userRepository
            ->shouldReceive('find')
            ->once()
            ->with($userId)
            ->andReturn($this->user);

        $this->employeeRepository
            ->shouldReceive('create')
            ->twice()
            ->andReturn(Employee::factory()->make());

        Cache::shouldReceive('forget')->twice();

        $result = $this->employeeService->processCsvImport($filePath, $userId);

        $this->assertEquals(2, $result['processedCount']);
        $this->assertEquals(0, $result['errorCount']);
        $this->assertEquals(2, $result['totalLines']);
        $this->assertEmpty($result['validationErrors']);

        // Assert file was deleted
        $this->assertFalse(Storage::disk('local')->exists($filePath));
    }

    #[Test]
    public function it_throws_exception_when_user_not_found_for_csv_import()
    {
        $userId = 999;
        $filePath = 'imports/test.csv';

        $this->userRepository
            ->shouldReceive('find')
            ->once()
            ->with($userId)
            ->andReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Usuário não encontrado');

        $this->employeeService->processCsvImport($filePath, $userId);
    }

    #[Test]
    public function it_throws_exception_when_file_not_found_for_csv_import()
    {
        $userId = 1;
        $filePath = 'imports/nonexistent.csv';

        $this->userRepository
            ->shouldReceive('find')
            ->once()
            ->with($userId)
            ->andReturn($this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Arquivo não encontrado');

        $this->employeeService->processCsvImport($filePath, $userId);
    }

    #[Test]
    public function it_throws_exception_when_required_headers_are_missing_empty_file()
    {
        $userId = 1;
        $filePath = 'imports/empty.csv';

        Storage::disk('local')->put($filePath, '');

        $this->userRepository
            ->shouldReceive('find')
            ->once()
            ->with($userId)
            ->andReturn($this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cabeçalhos obrigatórios ausentes');

        $this->employeeService->processCsvImport($filePath, $userId);
    }

    #[Test]
    public function it_throws_exception_when_required_headers_are_missing()
    {
        $userId = 1;
        $filePath = 'imports/invalid_headers.csv';
        $csvContent = "name,email\nJoão Silva,joao@example.com";

        Storage::disk('local')->put($filePath, $csvContent);

        $this->userRepository
            ->shouldReceive('find')
            ->once()
            ->with($userId)
            ->andReturn($this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cabeçalhos obrigatórios ausentes');

        $this->employeeService->processCsvImport($filePath, $userId);
    }

    #[Test]
    public function it_processes_csv_with_validation_errors()
    {
        $userId = 1;
        $filePath = 'imports/invalid_data.csv';
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,invalid-email,invalid-cpf,São Paulo,SP\n";
        $csvContent .= 'Maria Santos,maria@example.com,52998224725,Rio de Janeiro,INVALID_STATE';

        Storage::disk('local')->put($filePath, $csvContent);

        $this->userRepository
            ->shouldReceive('find')
            ->once()
            ->with($userId)
            ->andReturn($this->user);

        $result = $this->employeeService->processCsvImport($filePath, $userId);

        $this->assertEquals(0, $result['processedCount']);
        $this->assertEquals(2, $result['errorCount']);
        $this->assertEquals(2, $result['totalLines']);
        $this->assertNotEmpty($result['validationErrors']);
    }

    #[Test]
    public function it_handles_csv_lines_with_incorrect_column_count()
    {
        $userId = 1;
        $filePath = 'imports/incorrect_columns.csv';
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@example.com\n"; // Missing columns
        $csvContent .= 'Maria Santos,maria@example.com,52998224725,Rio de Janeiro,RJ,extra'; // Extra column

        Storage::disk('local')->put($filePath, $csvContent);

        $this->userRepository
            ->shouldReceive('find')
            ->once()
            ->with($userId)
            ->andReturn($this->user);

        $result = $this->employeeService->processCsvImport($filePath, $userId);

        $this->assertEquals(0, $result['processedCount']);
        $this->assertEquals(2, $result['errorCount']);
        $this->assertEquals(2, $result['totalLines']);
        $this->assertNotEmpty($result['validationErrors']);
    }

    #[Test]
    public function it_converts_state_names_to_abbreviations()
    {
        $service = new EmployeeService($this->employeeRepository, $this->userRepository);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getShortStateName');
        $method->setAccessible(true);

        // Test conversion from full name to abbreviation
        $this->assertEquals('SP', $method->invoke($service, 'São Paulo'));
        $this->assertEquals('RJ', $method->invoke($service, 'Rio de Janeiro'));

        // Test abbreviation remains unchanged
        $this->assertEquals('SP', $method->invoke($service, 'SP'));
        $this->assertEquals('RJ', $method->invoke($service, 'RJ'));

        // Test invalid state returns as is
        $this->assertEquals('INVALID', $method->invoke($service, 'INVALID'));
    }

    #[Test]
    public function it_validates_employee_data_correctly()
    {
        $service = new EmployeeService($this->employeeRepository, $this->userRepository);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('validateEmployeeData');
        $method->setAccessible(true);

        // Valid data
        $validData = [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'cpf' => '11144477735',
            'city' => 'São Paulo',
            'state' => 'SP',
        ];

        $errors = $method->invoke($service, $validData, 1);
        $this->assertEmpty($errors);

        // Invalid data
        $invalidData = [
            'name' => '',
            'email' => 'invalid-email',
            'cpf' => '123',
            'city' => '',
            'state' => 'INVALID',
        ];

        $errors = $method->invoke($service, $invalidData, 2);
        $this->assertNotEmpty($errors);
        $this->assertGreaterThan(0, count($errors));
    }

    #[Test]
    public function it_sends_processing_result_email()
    {
        $result = [
            'processedCount' => 5,
            'errorCount' => 2,
            'totalLines' => 7,
            'validationErrors' => ['Linha 2: Email inválido', 'Linha 5: CPF inválido'],
        ];

        $service = new EmployeeService($this->employeeRepository, $this->userRepository);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('sendProcessingResultEmail');
        $method->setAccessible(true);

        // Just test that the method can be called without errors
        $method->invoke($service, $this->user, $result);

        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    #[Test]
    public function it_sends_error_email()
    {
        $errors = ['Arquivo não encontrado', 'Formato inválido'];

        $service = new EmployeeService($this->employeeRepository, $this->userRepository);
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('sendErrorEmail');
        $method->setAccessible(true);

        // Just test that the method can be called without errors
        $method->invoke($service, $this->user, $errors);

        $this->assertTrue(true); // If we get here, the method executed successfully
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
