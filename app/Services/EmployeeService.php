<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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

        return Cache::store('redis')->remember($key, config('employees.cache_ttl'), function () use ($userId) {
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

    public function updateEmployee(Employee $employee, array $data): Employee
    {
        $this->employeeRepository->update($employee, $data);

        Cache::store('redis')->forget($this->getUserEmployeesCacheKey($employee->user_id));

        return $employee;
    }

    /**
     * Delete an employee
     */
    public function deleteEmployee(Employee $employee): bool
    {
        $deleted = $this->employeeRepository->delete($employee);

        if ($deleted) {
            Cache::store('redis')->forget($this->getUserEmployeesCacheKey($employee->user_id));
        }

        return $deleted;
    }

    private function getUserEmployeesCacheKey(int $userId): string
    {
        return "user:{$userId}:employees";
    }
}
