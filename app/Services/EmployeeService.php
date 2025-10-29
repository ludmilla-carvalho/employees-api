<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class EmployeeService
{
    public function __construct(protected EmployeeRepository $employeeRepository) {}

    /**
     * Get all employees for the authenticated user
     */
    public function getUserEmployees(): Collection
    {
        return $this->employeeRepository->findAllBy('user_id', Auth::id());
    }

    /**
     * Create a new employee
     */
    public function createEmployee(array $data): Employee
    {
        $data['user_id'] = Auth::id();

        return $this->employeeRepository->create($data);
    }

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $this->employeeRepository->update($employee, $data);

        return $employee;
    }

    /**
     * Delete an employee
     */
    public function deleteEmployee(Employee $employee): bool
    {
        return $this->employeeRepository->delete($employee);
    }
}
