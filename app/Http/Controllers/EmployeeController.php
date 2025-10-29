<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Responses\ApiResponse;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    /**
     * Display a listing of employees
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $employees = Employee::with('user')->paginate($perPage);

        return ApiResponse::success([
            'employees' => EmployeeResource::collection($employees->items()),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
            ],
        ], 'Employees retrieved successfully');
    }

    /**
     * Store a newly created employee
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->validated());
        $employee->load('user');

        return ApiResponse::created(
            new EmployeeResource($employee),
            'Employee created successfully'
        );
    }

    /**
     * Display the specified employee
     */
    public function show(Employee $employee): JsonResponse
    {
        $employee->load('user');

        return ApiResponse::success(
            new EmployeeResource($employee),
            'Employee retrieved successfully'
        );
    }

    /**
     * Update the specified employee
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee->update($request->validated());
        $employee->load('user');

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
        $employee->delete();

        return ApiResponse::deleted('Employee deleted successfully');
    }

    /**
     * Search employees by name, email or CPF
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');

        if (empty($query)) {
            return ApiResponse::validationError('Query parameter "q" is required');
        }

        $employees = Employee::with('user')
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('cpf', 'LIKE', "%{$query}%")
            ->paginate($request->get('per_page', 15));

        return ApiResponse::success([
            'employees' => EmployeeResource::collection($employees->items()),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'per_page' => $employees->perPage(),
                'total' => $employees->total(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
            ],
            'query' => $query,
        ], 'Search completed successfully');
    }
}
