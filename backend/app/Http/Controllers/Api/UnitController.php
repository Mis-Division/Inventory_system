<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ModelUnits;

class UnitController extends Controller
{
   public function displayUnit(Request $request)
{
    try {
        $search = $request->query('search');

        $query = ModelUnits::select('unit_name');

        if (!empty($search)) {
            $query->where('unit_name', 'LIKE', "%{$search}%");
        }

        // return all results sorted alphabetically
        $units = $query->orderBy('unit_name', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => "Units list",
            'data' => $units
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch units',
            'message' => $e->getMessage()
        ], 500);
    }
}


}
