<?php

namespace App\Jobs;

use App\Enums\BrazilianState;
use App\Models\User;
use App\Services\EmployeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    public function handle(EmployeeService $employeeService)
    {
        $user = User::find($this->userId);

        // Usa o Storage facade que respeita a configuração do disco
        if (! Storage::disk('local')->exists($this->filePath)) {
            $this->sendErrorEmail($user, [
                'Arquivo não encontrado: '.$this->filePath,
                'Disco: local',
                'Root do disco: '.Storage::disk('local')->path(''),
                'Caminho completo tentado: '.Storage::disk('local')->path($this->filePath),
                'Arquivos disponíveis na pasta imports:',
                ...Storage::disk('local')->files('imports'),
                'Todos os arquivos no disco:',
                ...Storage::disk('local')->allFiles(),
            ]);

            return;
        }

        // Lê o arquivo usando o Storage
        $fileContent = Storage::disk('local')->get($this->filePath);
        $rows = array_map('str_getcsv', explode("\n", trim($fileContent)));

        if (empty($rows)) {
            $this->sendErrorEmail($user, ['Arquivo CSV está vazio']);

            return;
        }

        $header = array_map('trim', array_shift($rows));

        $requiredHeaders = ['name', 'email', 'cpf', 'city', 'state'];
        $missingHeaders = array_diff($requiredHeaders, $header);

        if (! empty($missingHeaders)) {
            $this->sendErrorEmail($user, [
                'Cabeçalhos obrigatórios ausentes: '.implode(', ', $missingHeaders),
                'Cabeçalhos encontrados: '.implode(', ', $header),
                'Cabeçalhos esperados: '.implode(', ', $requiredHeaders),
            ]);

            return;
        }

        $validationErrors = [];
        $processedCount = 0;
        $errorCount = 0;

        foreach ($rows as $lineNumber => $row) {
            $actualLineNumber = $lineNumber + 2; // +2 porque removemos o header e array é 0-indexed

            // Verifica se a linha tem o número correto de colunas
            if (count($row) !== count($header)) {
                $validationErrors[] = "Linha {$actualLineNumber}: Número de colunas incorreto. Esperado: ".count($header).', Encontrado: '.count($row);
                $errorCount++;

                continue;
            }

            $data = array_combine($header, array_map('trim', $row));

            // Remove campos vazios e substitui por null
            $data = array_map(function ($value) {
                return empty($value) ? null : $value;
            }, $data);

            $data['state'] = $this->getShortStateName($data['state']);
            $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);

            // Valida os dados da linha
            $lineErrors = $this->validateEmployeeData($data, $actualLineNumber);

            if (! empty($lineErrors)) {
                $validationErrors = array_merge($validationErrors, $lineErrors);
                $errorCount++;

                continue;
            }

            try {
                $employeeService->createEmployeeForUser($this->userId, $data);
                $processedCount++;
            } catch (\Exception $e) {
                $validationErrors[] = "Linha {$actualLineNumber}: Erro ao criar funcionário - ".$e->getMessage();
                $errorCount++;
            }
        }

        // Envia notificação por e-mail
        $this->sendSuccessEmail($user, $processedCount, $errorCount, $validationErrors);

        // Remove o arquivo após o processamento para economizar espaço
        try {
            Storage::disk('local')->delete($this->filePath);
        } catch (\Exception $e) {
            // Log do erro mas não falha o job por causa disso
            Log::warning('Não foi possível deletar o arquivo após processamento: '.$e->getMessage());
        }
    }

    /**
     * Valida os dados de um funcionário
     */
    private function validateEmployeeData(array $data, int $lineNumber): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:employees,email',
            'cpf' => 'required|string|cpf|size:11|max:14|unique:employees,cpf',
            'city' => 'required|string|max:255',
            'state' => ['required', Rule::enum(BrazilianState::class)],
        ];

        $messages = [
            'name.required' => 'Nome é obrigatório',
            'name.max' => 'Nome não pode ter mais de 255 caracteres',
            'email.required' => 'E-mail é obrigatório',
            'email.email' => 'E-mail deve ter um formato válido',
            'email.max' => 'E-mail não pode ter mais de 255 caracteres',
            'cpf.required' => 'CPF é obrigatório',
            'cpf.max' => 'CPF não pode ter mais de 14 caracteres',
            'city.required' => 'Cidade é obrigatória',
            'city.max' => 'Cidade não pode ter mais de 255 caracteres',
            'state.required' => 'Estado é obrigatório',
            'state.enum' => 'Estado deve ser um estado brasileiro válido (ex: SP ou São Paulo)',
        ];

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $error) {
                $errors[] = "Linha {$lineNumber}: {$error}";
            }

            return $errors;
        }

        // // Validação adicional de CPF
        // $cpf = preg_replace('/\D/', '', $data['cpf']);
        // if (! $this->isValidCpf($cpf)) {
        //     return ["Linha {$lineNumber}: CPF inválido"];
        // }

        // Verifica duplicatas de email e CPF no banco
        // $existingEmployee = \App\Models\Employee::where('email', $data['email'])
        //     ->orWhere('cpf', $cpf)
        //     ->first();

        // if ($existingEmployee) {
        //     if ($existingEmployee->email === $data['email']) {
        //         return ["Linha {$lineNumber}: E-mail já cadastrado"];
        //     }
        //     if ($existingEmployee->cpf === $cpf) {
        //         return ["Linha {$lineNumber}: CPF já cadastrado"];
        //     }
        // }

        return [];
    }

    protected function getShortStateName(string $state): ?string
    {
        if (strlen($state) > 2) {
            $slugState = strtoupper(Str::slug($state, '_'));
            if (in_array($slugState, BrazilianState::getNames())) {
                return BrazilianState::{$slugState}->value;
            }
        }

        return $state;
    }

    /**
     * Envia e-mail de sucesso com resumo do processamento
     */
    private function sendSuccessEmail(User $user, int $processedCount, int $errorCount, array $validationErrors): void
    {
        $subject = 'Importação de colaboradores concluída';
        $totalLines = $processedCount + $errorCount;

        $message = "Processamento concluído!\n\n";
        $message .= "Resumo:\n";
        $message .= "- Total de linhas processadas: {$totalLines}\n";
        $message .= "- Funcionários criados com sucesso: {$processedCount}\n";
        $message .= "- Linhas com erro: {$errorCount}\n\n";

        if (! empty($validationErrors)) {
            $message .= "Detalhes dos erros:\n";
            foreach ($validationErrors as $error) {
                $message .= "- {$error}\n";
            }
        }

        Mail::raw($message, function ($mail) use ($user, $subject) {
            $mail->to($user->email)->subject($subject);
        });
    }

    /**
     * Envia e-mail de erro
     */
    private function sendErrorEmail(User $user, array $errors): void
    {
        $subject = 'Erro na importação de colaboradores';
        $message = "Ocorreram erros durante o processamento do arquivo CSV:\n\n";

        foreach ($errors as $error) {
            $message .= "- {$error}\n";
        }

        Mail::raw($message, function ($mail) use ($user, $subject) {
            $mail->to($user->email)->subject($subject);
        });
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $user = User::find($this->userId);

        if ($user) {
            $this->sendErrorEmail($user, [
                'O processamento do arquivo falhou após múltiplas tentativas.',
                'Erro: '.$exception->getMessage(),
                'Arquivo: '.$this->filePath,
            ]);
        }
    }
}
