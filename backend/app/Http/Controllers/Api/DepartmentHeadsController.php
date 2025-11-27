<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeptHead;
use App\Models\User;

class DepartmentHeadsController extends Controller
{
    /**
     * Get the department head for a given user department
     */
    public function getDeptHead(Request $request)
    {
        // Example: /api/dept-head?user_id=5
        $userId = $request->query('user_id');

        // Get the user first
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }

        // Find department head with the same department
        $deptHead = DeptHead::where('department', $user->department)->first();

        if (!$deptHead) {
            return response()->json([
                'message' => 'No department head found for this department'
            ], 404);
        }

        // Return the matched department head info
        return response()->json([
            'dept_head_name' => $deptHead->depthead_name,
            'department' => $deptHead->department,
        ], 200);
    }
}
