<?php

namespace App\Services;

use App\Enums\BrazilianState;
use App\Models\Employee;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeService
{
    public function __construct(protected EmployeeRepository $employeeRepository) {}

    /**
     * Get all employees for the authenticated user (cached in Redis)
     */
    public function getUserEmployees(): Collection
    {
        $userId = Auth::id();

        $key = $this->getUserEmployeesCacheKey($userId);

        return Cache::remember($key, config('employees.cache_ttl'), function () use ($userId) {
            return $this->employeeRepository->findAllBy('user_id', $userId);
        });
    }

    /**
     * Create a new employee
     */
    public function createEmployee(array $data): Employee
    {
        $data['user_id'] = Auth::id();

        $employee = $this->employeeRepository->create($data);

        Cache::store('redis')->forget($this->getUserEmployeesCacheKey($data['user_id']));

        return $employee;
    }

    /**
     * Create a new employee for a specific user (used in jobs)
     */
    public function createEmployeeForUser(int $userId, array $data): Employee
    {
        $data['user_id'] = $userId;

        $employee = $this->employeeRepository->create($data);

        Cache::forget($this->getUserEmployeesCacheKey($userId));

        return $employee;
    }

    /**
     * Update an existing employee
     */
    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $this->employeeRepository->update($employee, $data);

        Cache::forget($this->getUserEmployeesCacheKey($employee->user_id));

        return $employee;
    }

    /**
     * Delete an employee
     */
    public function deleteEmployee(Employee $employee): bool
    {
        $deleted = $this->employeeRepository->delete($employee);

        if ($deleted) {
            Cache::forget($this->getUserEmployeesCacheKey($employee->user_id));
        }

        return $deleted;
    }

    /**
     * Get the cache key for a user's employees
     */
    private function getUserEmployeesCacheKey(int $userId): string
    {
        return "user:{$userId}:employees";
    }

    /**
     * Process CSV file and import employees
     */
    public function processCsvImport(string $filePath, int $userId): array
    {
        $user = User::find($userId);

        if (! $user) {
            throw new \Exception('Usuário não encontrado');
        }

        // Verifies if the file exists
        if (! Storage::disk('local')->exists($filePath)) {
            $this->sendErrorEmail($user, [
                'Arquivo não encontrado: '.$filePath,
                'Disco: local',
                'Root do disco: '.Storage::disk('local')->path(''),
                'Caminho completo tentado: '.Storage::disk('local')->path($filePath),
                'Arquivos disponíveis na pasta imports:',
                ...Storage::disk('local')->files('imports'),
            ]);

            throw new \Exception('Arquivo não encontrado: '.$filePath);
        }

        // Read file content
        $fileContent = Storage::disk('local')->get($filePath);
        $rows = array_map('str_getcsv', explode("\n", trim($fileContent)));

        if (empty($rows)) {
            $this->sendErrorEmail($user, ['Arquivo CSV está vazio']);
            throw new \Exception('Arquivo CSV está vazio');
        }

        $header = array_map('trim', array_shift($rows));
        $requiredHeaders = ['name', 'email', 'cpf', 'city', 'state'];
        $missingHeaders = array_diff($requiredHeaders, $header);

        if (! empty($missingHeaders)) {
            $errors = [
                'Cabeçalhos obrigatórios ausentes: '.implode(', ', $missingHeaders),
                'Cabeçalhos encontrados: '.implode(', ', $header),
                'Cabeçalhos esperados: '.implode(', ', $requiredHeaders),
            ];
            $this->sendErrorEmail($user, $errors);
            throw new \Exception('Cabeçalhos obrigatórios ausentes: '.implode(', ', $missingHeaders));
        }

        $validationErrors = [];
        $processedCount = 0;
        $errorCount = 0;

        foreach ($rows as $lineNumber => $row) {
            $actualLineNumber = $lineNumber + 2; // +2 because we removed the header and array is 0-indexed

            // Veryies if the line has the correct number of columns
            if (count($row) !== count($header)) {
                $validationErrors[] = "Linha {$actualLineNumber}: Número de colunas incorreto. Esperado: ".count($header).', Encontrado: '.count($row);
                $errorCount++;

                continue;
            }

            $data = array_combine($header, array_map('trim', $row));

            // Remove empty strings and set as null
            $data = array_map(function ($value) {
                return empty($value) ? null : $value;
            }, $data);

            // Data validation
            $lineErrors = $this->validateEmployeeData($data, $actualLineNumber);

            if (! empty($lineErrors)) {
                $validationErrors = array_merge($validationErrors, $lineErrors);
                $errorCount++;

                continue;
            }

            try {
                $this->createEmployeeForUser($userId, $data);
                $processedCount++;
            } catch (\Exception $e) {
                $validationErrors[] = "Linha {$actualLineNumber}: Erro ao criar funcionário - ".$e->getMessage();
                $errorCount++;
            }
        }

        $result = [
            'processedCount' => $processedCount,
            'errorCount' => $errorCount,
            'validationErrors' => $validationErrors,
            'totalLines' => $processedCount + $errorCount,
        ];

        $this->sendProcessingResultEmail($user, $result);

        // Remove file after processing
        try {
            Storage::disk('local')->delete($filePath);
        } catch (\Exception $e) {
            Log::warning('Error deleting file after processing: '.$e->getMessage());
        }

        return $result;
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
            'email.unique' => 'E-mail já está em uso',
            'cpf.required' => 'CPF é obrigatório',
            'cpf.max' => 'CPF não pode ter mais de 14 caracteres',
            'cpf.unique' => 'CPF já está em uso',
            'city.required' => 'Cidade é obrigatória',
            'city.max' => 'Cidade não pode ter mais de 255 caracteres',
            'state.required' => 'Estado é obrigatório',
            'state.enum' => 'Estado deve ser um estado brasileiro válido (ex: SP ou São Paulo)',
        ];

        $data['state'] = $this->getShortStateName($data['state']);
        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->all() as $error) {
                $errors[] = "Linha {$lineNumber}: {$error}";
            }

            return $errors;
        }

        return [];
    }

    /**
     * Converts state name to abbreviation
     */
    private function getShortStateName(string $state): ?string
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
     * Sends processing result email
     */
    private function sendProcessingResultEmail(User $user, array $result): void
    {
        $subject = 'Importação de colaboradores concluída';

        $message = "Processamento concluído!\r\n\n";
        $message .= "Resumo:\r\n";
        $message .= "- Total de linhas processadas: {$result['totalLines']}\r\n";
        $message .= "- Funcionários criados com sucesso: {$result['processedCount']}\r\n";
        $message .= "- Linhas com erros de validação: {$result['errorCount']}\r\n\n";

        if (! empty($result['validationErrors'])) {
            $message .= "Detalhes dos erros:\r\n";
            foreach ($result['validationErrors'] as $error) {
                $message .= "- {$error}\r\n";
            }
        }

        Mail::raw($message, function ($mail) use ($user, $subject) {
            $mail->to($user->email)->subject($subject);
        });
    }

    /**
     * Sends error email
     */
    private function sendErrorEmail(User $user, array $errors): void
    {
        $subject = 'Erro na importação de colaboradores';
        $message = "Ocorreram erros durante o processamento do arquivo CSV:\r\n";

        foreach ($errors as $error) {
            $message .= "- {$error}\n";
        }

        Mail::raw($message, function ($mail) use ($user, $subject) {
            $mail->to($user->email)->subject($subject);
        });
    }
}
