<?php

namespace Tests\Feature;

use App\Jobs\ProcessEmployeeCsvJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class EmployeeControllerImportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);

        // Fake the local disk to avoid actual file storage
        Storage::fake('local');

        // Fake the queue to avoid actual job dispatching
        Queue::fake();
    }

    /**
     * Test successful CSV file import
     */
    public function test_imports_csv_file_successfully()
    {
        // Create a fake CSV file
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= "João Silva,joao@example.com,11144477735,São Paulo,SP\n";
        $csvContent .= 'Maria Santos,maria@example.com,52998224725,Rio de Janeiro,RJ';

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees/import', [
            'file' => $file,
        ]);

        $response->assertStatus(Response::HTTP_ACCEPTED)
            ->assertJsonStructure([
                'message',
            ])
            ->assertJson([
                'message' => 'The import of employee data will be processed shortly. You will be notified when it is complete.',
            ]);

        // Assert that the file was stored
        $this->assertTrue(Storage::disk('local')->exists('imports/'.$file->hashName()));

        // Assert that the job was dispatched
        Queue::assertPushed(ProcessEmployeeCsvJob::class);
    }

    /**
     * Test that file is required for import
     */
    public function test_requires_file_for_import()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees/import', []);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * Test requires CSV or TXT file format
     */
    public function test_requires_csv_or_txt_file_format()
    {
        $file = UploadedFile::fake()->create('employees.pdf', 100);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees/import', [
            'file' => $file,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['file'])
            ->assertJsonFragment([
                'file' => ['The file field must be a file of type: csv, txt.'],
            ]);
    }

    /**
     * Test rejects files larger than 2MB
     */
    public function test_rejects_files_larger_than_2mb()
    {
        // Create a file larger than 2MB (2048KB)
        $file = UploadedFile::fake()->create('employees.csv', 2049);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees/import', [
            'file' => $file,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['file'])
            ->assertJsonFragment([
                'file' => ['The file field must not be greater than 2048 kilobytes.'],
            ]);
    }

    /**
     * Test requires authentication for import
     */
    public function test_requires_authentication_for_import()
    {
        $file = UploadedFile::fake()->createWithContent('employees.csv', 'name,email,cpf,city,state');

        $response = $this->postJson('/api/employees/import', [
            'file' => $file,
        ]);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Test stores file in local disk imports directory
     */
    public function test_stores_file_in_local_disk_imports_directory()
    {
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= 'Test User,test@example.com,11144477735,Test City,SP';

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees/import', [
            'file' => $file,
        ]);

        // Assert that the file was stored in the imports directory
        $this->assertTrue(Storage::disk('local')->exists('imports/'.$file->hashName()));
    }

    /**
     * Test dispatches job with correct parameters
     */
    public function test_dispatches_job_with_correct_parameters()
    {
        $csvContent = "name,email,cpf,city,state\n";
        $csvContent .= 'Test User,test@example.com,11144477735,Test City,SP';

        $file = UploadedFile::fake()->createWithContent('employees.csv', $csvContent);

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees/import', [
            'file' => $file,
        ]);

        // Assert that the job was dispatched with correct parameters
        Queue::assertPushed(ProcessEmployeeCsvJob::class, function ($job) use ($file) {
            $reflectionClass = new \ReflectionClass($job);
            $filePathProperty = $reflectionClass->getProperty('filePath');
            $filePathProperty->setAccessible(true);
            $userIdProperty = $reflectionClass->getProperty('userId');
            $userIdProperty->setAccessible(true);

            return $filePathProperty->getValue($job) === 'imports/'.$file->hashName() &&
                $userIdProperty->getValue($job) === $this->user->id;
        });
    }

    /**
     * Test dispatches job to default queue
     */
    public function test_dispatches_job_to_default_queue()
    {
        $file = UploadedFile::fake()->createWithContent('employees.csv', 'name,email,cpf,city,state');

        $this->withHeaders([
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ])->postJson('/api/employees/import', [
            'file' => $file,
        ]);

        Queue::assertPushedOn('default', ProcessEmployeeCsvJob::class);
    }
}
