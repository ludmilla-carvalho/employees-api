<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportEmployeeRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Responses\ApiResponse;
use App\Jobs\ProcessEmployeeCsvJob;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function __construct(protected EmployeeService $employeeService) {}

    /**
     * Display a listing of the user's employees
     */
    public function index(): JsonResponse
    {
        $employees = $this->employeeService->getUserEmployees();

        return ApiResponse::success(
            EmployeeResource::collection($employees),
            'Employees retrieved successfully'
        );
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee): JsonResponse
    {
        $this->authorize('view', $employee);

        return ApiResponse::success(
            new EmployeeResource($employee),
            'Employee retrieved successfully'
        );
    }

    /**
     * Store a newly created employee
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->createEmployee($request->validated());

        return ApiResponse::created(
            new EmployeeResource($employee),
            'Employee created successfully'
        );
    }

    /**
     * Update the specified employee
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $this->authorize('update', $employee);

        $employee = $this->employeeService->updateEmployee($employee, $request->validated());

        return ApiResponse::updated(
            new EmployeeResource($employee),
            'Employee updated successfully'
        );
    }

    /**
     * Remove the specified employee
     */
    public function destroy(Employee $employee): JsonResponse
    {
        $this->authorize('delete', $employee);

        $this->employeeService->deleteEmployee($employee);

        return ApiResponse::deleted('Employee deleted successfully');
    }

    /**
     * Import employees from CSV file
     */
    public function import(ImportEmployeeRequest $request): JsonResponse
    {
        $request->validated();

        // Salva na pasta imports dentro do disco local (storage/app/private/imports)
        $path = $request->file('file')->store('imports', 'local');

        // $delaySeconds = config('employees.job_import_delay', 0);

        ProcessEmployeeCsvJob::dispatch($path, Auth::id())
            ->onQueue('default');

        $message = 'Employees import will be processed soon. You will be notified once complete.';

        return ApiResponse::accepted(null, $message);
    }
}
