<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function registerEmployee(Request $request)
    {
        $user = $request->user();

        // Check role
        if (! $user || $user->role !== 'Administrator') {
            return response()->json([
                'message' => 'Forbidden: Only admins can add employees.',
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'employee_id' => 'required|string|max:5',
            'employee_name' => 'required|string|max:255',
            'employee_department' => 'required|string|max:255',
        ]);

        try {
            $employee = Employee::create($validated);

            return response()->json([
                'message' => 'Employee registered successfully!',
                'employee' => $employee,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to register employee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getEmployee()
    {
        try {
            $employees = Employee::all();

            return response()->json([
                'employees' => $employees,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch employees',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateEmployee(Request $request, $id)
    {
        $user = $request->user();

        // ✅ Allow only admins to update employees
        if (! $user || $user->role !== 'Administrator') {
            return response()->json([
                'message' => 'Forbidden: Only admins can update employees.',
            ], 403);
        }

        // ✅ Validate input
        $request->validate([
            'employee_id' => 'sometimes|string|max:5',
            'employee_name' => 'sometimes|string|max:255',
            'employee_department' => 'sometimes|string|max:255',
        ]);

        // ✅ Find employee
        $employee = Employee::find($id);
        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found',
            ], 404);
        }

        // ✅ Update employee
        $employee->update($request->only([
            'employee_id',
            'employee_name',
            'employee_department',
        ]));

        return response()->json([
            'message' => 'Employee updated successfully!',
            'employee' => $employee,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // ✅ Only admins can delete employees
        if (! $user || $user->role !== 'Administrator') {
            return response()->json([
                'message' => 'Forbidden: Only admins can delete employees.',
            ], 403);
        }

        // ✅ Find employee
        $employee = Employee::find($id);
        if (! $employee) {
            return response()->json([
                'message' => 'Employee not found',
            ], 404);
        }

        // ✅ Delete employee
        $employee->delete();

        return response()->json([
            'message' => 'Employee deleted successfully!',
        ], 200);
    }
}
