<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessEmployeeCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    protected $userId;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 300; // 5 minutos

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [10, 30, 60]; // 10s, 30s, 60s entre tentativas

    public function __construct(string $filePath, int $userId)
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(EmployeeService $employeeService): void
    {
        try {
            $employeeService->processCsvImport($this->filePath, $this->userId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $user = User::find($this->userId);

        if ($user) {
            $subject = 'Erro na importação de colaboradores';
            $message = "O processamento do arquivo falhou após múltiplas tentativas.\r\n\n";
            $message .= 'Erro: '.$exception->getMessage()."\r\n";
            $message .= 'Arquivo: '.$this->filePath."\r\n";

            Mail::raw($message, function ($mail) use ($user, $subject) {
                $mail->to($user->email)->subject($subject);
            });
        }
    }
}
