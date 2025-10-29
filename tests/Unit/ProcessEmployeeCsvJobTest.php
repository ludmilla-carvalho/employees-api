<?php

namespace Tests\Unit;

use App\Jobs\ProcessEmployeeCsvJob;
use App\Models\User;
use App\Services\EmployeeService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProcessEmployeeCsvJobTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $employeeService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeService = Mockery::mock(EmployeeService::class);
        $this->user = User::factory()->create(['email' => 'test@example.com']);

        Mail::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_constructed_with_file_path_and_user_id()
    {
        $filePath = 'storage/app/imports/test.csv';
        $userId = $this->user->id;

        $job = new ProcessEmployeeCsvJob($filePath, $userId);

        $this->assertInstanceOf(ProcessEmployeeCsvJob::class, $job);
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals([10, 30, 60], $job->backoff);
    }

    #[Test]
    public function it_can_be_dispatched_to_queue()
    {
        Queue::fake();

        $filePath = 'storage/app/imports/test.csv';
        $userId = $this->user->id;

        ProcessEmployeeCsvJob::dispatch($filePath, $userId);

        Queue::assertPushed(ProcessEmployeeCsvJob::class, function (ProcessEmployeeCsvJob $job) use ($filePath, $userId) {
            // Usar reflection para acessar propriedades protegidas
            $reflection = new \ReflectionClass($job);

            $filePathProperty = $reflection->getProperty('filePath');
            $filePathProperty->setAccessible(true);
            $jobFilePath = $filePathProperty->getValue($job);

            $userIdProperty = $reflection->getProperty('userId');
            $userIdProperty->setAccessible(true);
            $jobUserId = $userIdProperty->getValue($job);

            return $jobFilePath === $filePath && $jobUserId === $userId;
        });
    }

    #[Test]
    public function it_processes_csv_import_successfully_when_handled()
    {
        $filePath = 'storage/app/imports/test.csv';
        $userId = $this->user->id;

        $expectedResult = [
            'processedCount' => 5,
            'errorCount' => 0,
            'validationErrors' => [],
            'totalLines' => 5,
        ];

        $this->employeeService
            ->shouldReceive('processCsvImport')
            ->once()
            ->with($filePath, $userId)
            ->andReturn($expectedResult);

        $job = new ProcessEmployeeCsvJob($filePath, $userId);
        $job->handle($this->employeeService);

        // Se chegou até aqui sem exceções, o teste passou
        $this->assertTrue(true);
    }

    #[Test]
    public function it_throws_exception_when_employee_service_fails()
    {
        $filePath = 'storage/app/imports/test.csv';
        $userId = $this->user->id;
        $exceptionMessage = 'Erro no processamento do CSV';

        $this->employeeService
            ->shouldReceive('processCsvImport')
            ->once()
            ->with($filePath, $userId)
            ->andThrow(new Exception($exceptionMessage));

        $job = new ProcessEmployeeCsvJob($filePath, $userId);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $job->handle($this->employeeService);
    }

    #[Test]
    public function it_calls_failed_method_when_job_fails()
    {
        $filePath = 'storage/app/imports/test.csv';
        $userId = $this->user->id;
        $exception = new Exception('Erro no processamento');

        $job = new ProcessEmployeeCsvJob($filePath, $userId);

        // Testa se o método failed pode ser chamado sem erro
        try {
            $job->failed($exception);
            $this->assertTrue(true); // Se chegou até aqui, o método funcionou
        } catch (\Throwable $e) {
            $this->fail('O método failed lançou uma exceção: '.$e->getMessage());
        }
    }

    #[Test]
    public function it_handles_exception_details_in_failed_method()
    {
        $filePath = 'storage/app/imports/test.csv';
        $userId = $this->user->id;
        $exceptionMessage = 'Arquivo CSV corrompido';
        $exception = new Exception($exceptionMessage);

        $job = new ProcessEmployeeCsvJob($filePath, $userId);

        // Testa se o método failed pode ser chamado com diferentes exceções
        try {
            $job->failed($exception);
            $this->assertTrue(true); // Se chegou até aqui, o método funcionou
        } catch (\Throwable $e) {
            $this->fail('O método failed lançou uma exceção: '.$e->getMessage());
        }
    }

    #[Test]
    public function it_does_not_send_failure_email_when_user_not_found()
    {
        $filePath = 'storage/app/imports/test.csv';
        $nonExistentUserId = 999999;
        $exception = new Exception('Erro no processamento');

        $job = new ProcessEmployeeCsvJob($filePath, $nonExistentUserId);
        $job->failed($exception);

        Mail::assertNothingSent();
    }

    #[Test]
    public function it_handles_different_types_of_throwable_exceptions()
    {
        $filePath = 'storage/app/imports/test.csv';
        $userId = $this->user->id;

        // Testando com diferentes tipos de exceções
        $exceptions = [
            new Exception('Exception padrão'),
            new \RuntimeException('Runtime exception'),
            new \InvalidArgumentException('Invalid argument'),
        ];

        foreach ($exceptions as $exception) {
            $job = new ProcessEmployeeCsvJob($filePath, $userId);

            // Testa se o método failed pode ser chamado com diferentes tipos de exceção
            try {
                $job->failed($exception);
                $this->assertTrue(true); // Se chegou até aqui, o método funcionou
            } catch (\Throwable $e) {
                $this->fail('O método failed lançou uma exceção: '.$e->getMessage());
            }
        }
    }

    #[Test]
    public function it_preserves_job_properties_after_construction()
    {
        $filePath = 'storage/app/imports/employees_2023.csv';
        $userId = $this->user->id;

        $job = new ProcessEmployeeCsvJob($filePath, $userId);

        // Verificar se as propriedades foram definidas corretamente
        $reflection = new \ReflectionClass($job);

        $filePathProperty = $reflection->getProperty('filePath');
        $filePathProperty->setAccessible(true);
        $this->assertEquals($filePath, $filePathProperty->getValue($job));

        $userIdProperty = $reflection->getProperty('userId');
        $userIdProperty->setAccessible(true);
        $this->assertEquals($userId, $userIdProperty->getValue($job));
    }

    #[Test]
    public function it_has_correct_queue_configuration()
    {
        $job = new ProcessEmployeeCsvJob('test.csv', 1);

        // Verificar configurações de queue
        $this->assertEquals(3, $job->tries);
        $this->assertEquals(300, $job->timeout);
        $this->assertEquals([10, 30, 60], $job->backoff);
    }

    #[Test]
    public function it_implements_should_queue_interface()
    {
        $job = new ProcessEmployeeCsvJob('test.csv', 1);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }

    #[Test]
    public function it_uses_correct_traits()
    {
        $job = new ProcessEmployeeCsvJob('test.csv', 1);

        $traits = class_uses($job);

        $this->assertContains(\Illuminate\Bus\Queueable::class, $traits);
        $this->assertContains(\Illuminate\Foundation\Bus\Dispatchable::class, $traits);
        $this->assertContains(\Illuminate\Queue\InteractsWithQueue::class, $traits);
        $this->assertContains(\Illuminate\Queue\SerializesModels::class, $traits);
    }

    #[Test]
    public function it_can_handle_job_with_different_file_extensions()
    {
        $filePaths = [
            'storage/app/imports/employees.csv',
            'storage/app/imports/data.CSV',
            'storage/app/imports/workers.txt',
        ];

        foreach ($filePaths as $filePath) {
            $job = new ProcessEmployeeCsvJob($filePath, $this->user->id);
            $this->assertInstanceOf(ProcessEmployeeCsvJob::class, $job);
        }
    }

    #[Test]
    public function it_maintains_serialization_properties()
    {
        $filePath = 'storage/app/imports/test.csv';
        $userId = $this->user->id;

        $job = new ProcessEmployeeCsvJob($filePath, $userId);

        // Testar se o job pode ser serializado e desserialized (importante para queues)
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(ProcessEmployeeCsvJob::class, $unserialized);

        // Verificar se as propriedades foram mantidas
        $reflection = new \ReflectionClass($unserialized);

        $filePathProperty = $reflection->getProperty('filePath');
        $filePathProperty->setAccessible(true);
        $this->assertEquals($filePath, $filePathProperty->getValue($unserialized));

        $userIdProperty = $reflection->getProperty('userId');
        $userIdProperty->setAccessible(true);
        $this->assertEquals($userId, $userIdProperty->getValue($unserialized));
    }

    #[Test]
    public function it_has_proper_timeout_and_backoff_configuration()
    {
        $job = new ProcessEmployeeCsvJob('test.csv', 1);

        // Verificar configurações específicas para processamento de CSV
        $this->assertEquals(300, $job->timeout, 'Timeout deve ser 300 segundos (5 minutos)');
        $this->assertEquals(3, $job->tries, 'Deve ter 3 tentativas');
        $this->assertEquals([10, 30, 60], $job->backoff, 'Backoff deve ser progressivo: 10s, 30s, 60s');
    }

    #[Test]
    public function it_can_be_constructed_with_various_user_ids()
    {
        $userIds = [1, 100, 999999];
        $filePath = 'storage/app/imports/test.csv';

        foreach ($userIds as $userId) {
            $job = new ProcessEmployeeCsvJob($filePath, $userId);

            $reflection = new \ReflectionClass($job);
            $userIdProperty = $reflection->getProperty('userId');
            $userIdProperty->setAccessible(true);

            $this->assertEquals($userId, $userIdProperty->getValue($job));
        }
    }
}
