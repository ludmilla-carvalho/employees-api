<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\EmployeeRepository;

class EmployeeService
{
    public function __construct(protected EmployeeRepository $employeeRepository) {}

    /**
     * Create a new employee
     */
    public function createEmployee(array $data): Employee
    {
        $data['user_id'] = auth()->id();

        return $this->employeeRepository->create($data);
    }

    public function formatCPF(string $cpf): string
    {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
}
