<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Responses\ApiResponse;
use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;

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
}
