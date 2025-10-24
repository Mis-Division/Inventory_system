<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Items;
use Illuminate\Support\Facades\DB;

class ItemsController extends Controller
{
    public function CreateItemCode(Request $request){
        $request->validate([

            'ItemCode' => 'required|string|max:255|unique:tbl_item_code,ItemCode',
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'accounting_code' => 'nullable|string|max:255|unique:tbl_item_code,accounting_code',
        ]);

        DB::beginTransaction();

        try {
            // ✅ Create record
            $item = Items::create([
                'ItemCode' => $request->ItemCode,
                'product_name' =>$request->product_name,
                'description' => $request->description,
                'accounting_code' => $request->accounting_code,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Item code created successfully',
                'data' => $item
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to create item code',
                'message' => $e->getMessage()
            ], 500);
        }
    }
public function GetItemCode(Request $request)
{
    try {
        // Get search keyword and pagination parameters
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10); // Default 10 per page

        // Build query
        $query = Items::query();

        // Apply search if keyword provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('ItemCode', 'LIKE', "%{$search}%")
                  ->orWhere('product_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('accounting_code', 'LIKE', "%{$search}%");
            });
        }

        // Paginate results
        $items = $query->orderBy('created_at', 'asc')->paginate($perPage);

        // Return Vue-friendly JSON
        return response()->json([
            'success' => true,
            'message' => $search 
                ? "Item codes matching '{$search}' fetched successfully"
                : 'All item codes fetched successfully',
            'data' => $items->items(), // current page data
            'meta' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem()
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch item codes',
            'message' => $e->getMessage()
        ], 500);
    }
}


    public function GetItemCodeId($id)
        {
            try {
                $item = Items::find($id);

                if (!$item) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Item code not found',
                    ], 404);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Item code fetched successfully',
                    'data' => $item
                ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to fetch item code',
                    'message' => $e->getMessage()
                ], 500);
            }
        }

        public function UpdateItemCode(Request $request, $id)
        {
            try {
                $item = Items::find($id);

                if (!$item) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Item code not found',
                    ], 404);
                }

                $validated = $request->validate([
                    'ItemCode' => 'required|string|max:255|unique:tbl_item_code,ItemCode,' . $id . ',ItemCode_id',
                    'product_name' => 'required|string|max:255',
                    'description' => 'nullable|string|max:255',
                    'accounting_code' => 'nullable|string|max:255|unique:tbl_item_code,accounting_code,' . $id . ',ItemCode_id',
                ]);
$accountingCode = $request->input('accounting_code');
if ($accountingCode === '') {
    $accountingCode = null;
}
                $item->update([
                    'ItemCode' => $validated['ItemCode'],
                    'product_name' =>$validated['product_name'] ?? $item->product_name,
                    'description' => $validated['description'] ?? $item->description,
                    'accounting_code' => $accountingCode,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Item code updated successfully',
                    'data' => $item
                ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to update item code',
                    'message' => $e->getMessage()
                ], 500);
            }
        }


            public function DeleteItemCode($id)
                {
                    try {
                        $item = Items::find($id);

                        if (!$item) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Item code not found',
                            ], 404);
                        }

                        $item->delete();

                        return response()->json([
                            'success' => true,
                            'message' => 'Item code deleted successfully'
                        ], 200);

                    } catch (\Exception $e) {
                        return response()->json([
                            'success' => false,
                            'error' => 'Failed to delete item code',
                            'message' => $e->getMessage()
                        ], 500);
                    }
                }



}
