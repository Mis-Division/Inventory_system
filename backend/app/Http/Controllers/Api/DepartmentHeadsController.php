<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeptHead;
use App\Models\User;
use Illuminate\Support\Facades\DB;


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

    public function index(Request $request)
{
    try {
        $search = $request->query('search');

        $query = DB::table('tbl_depthead as d')
            ->select(
                'd.id',
                'd.depthead_name',
                'd.department'
            );

        // 🔍 SEARCH FILTER
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('d.depthead_name', 'LIKE', "%{$search}%")
                  ->orWhere('d.department', 'LIKE', "%{$search}%");
            });
        }

        $deptHeads = $query
            ->orderBy('d.depthead_name', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Department Heads List',
            'data'    => $deptHeads,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch department heads',
            'error'   => $e->getMessage(),
        ], 500);
    }
}

}

