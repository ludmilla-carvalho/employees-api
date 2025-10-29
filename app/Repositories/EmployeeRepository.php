<?php

namespace App\Repositories;

use App\Models\Employee;

class EmployeeRepository extends BaseRepository
{
    /**
     * Get model class name
     */
    protected function getModelClass(): string
    {
        return Employee::class;
    }
}
