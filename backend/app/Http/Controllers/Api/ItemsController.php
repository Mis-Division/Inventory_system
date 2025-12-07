<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Items;
use Illuminate\Support\Facades\DB;

class ItemsController extends Controller
{
    public function CreateItemCode(Request $request)
{
    $request->validate([
        // ❌ REMOVE ItemCode validation
        // 'ItemCode' => 'required|string|max:255|unique:tbl_item_code,ItemCode',

        'product_name'     => 'required|string|max:255',
        'description'      => 'required|string',
        'accounting_code'  => 'nullable|string|max:255',
        'item_category'    => 'required|string|max:255',
        'units'            => 'required|string|max:255',
    ]);

    DB::beginTransaction();

    try {
        // Only send fields you actually input
        $item = Items::create([
            // ❌ REMOVE THIS
            // 'ItemCode' => $request->ItemCode,

            'product_name'    => $request->product_name,
            'description'     => $request->description,
            'accounting_code' => $request->accounting_code,
            'item_category'   => $request->item_category,
            'units'           => $request->units,
        ]);

        // ItemCode is auto-generated in Items model
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
        // Get search keyword, category filter and pagination parameters
        $search   = $request->query('search');
        $category = $request->query('category'); // <-- NEW
        $perPage  = $request->query('per_page', 100000);

        // Build base query
        $query = Items::query();

        // Apply category filter if requested
        if (!empty($category)) {
            $query->where('item_category', $category);
        }

        // Apply search if keyword provided
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('ItemCode', 'LIKE', "%{$search}%")
                  ->orWhere('product_name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('accounting_code', 'LIKE', "%{$search}%")
                  ->orWhere('item_category', 'LIKE', "%{$search}%");
            });
        }

        // Paginate results
        $items = $query->orderBy('created_at', 'asc')->paginate($perPage);

        // Return Vue-friendly JSON
        return response()->json([
            'success' => true,
            'message' => 'Item codes fetched successfully.',
            'data'    => $items->items(),
            'meta'    => [
                'current_page' => $items->currentPage(),
                'per_page'     => $items->perPage(),
                'total'        => $items->total(),
                'last_page'    => $items->lastPage(),
                'from'         => $items->firstItem(),
                'to'           => $items->lastItem(),
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error'   => 'Failed to fetch item codes.',
            'message' => $e->getMessage(),
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
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'accounting_code' => 'nullable|string|max:255',
            'item_category' => 'required|string|max:255',
            'units' => 'required|string|max:255',
        ]);

        // Fix accounting code empty string
        $accountingCode = $request->input('accounting_code');
        if ($accountingCode === '') {
            $accountingCode = null;
        }

        // AUTO-REGENERATE ITEMCODE BASED ON UPDATED PRODUCT NAME
        $newItemCode = Items::generateItemCode($validated['product_name']);

        $item->update([
            'ItemCode' => $newItemCode,
            'product_name' => $validated['product_name'],
            'description' => $validated['description'] ?? $item->description,
            'accounting_code' => $accountingCode,
            'item_category' => $validated['item_category'],
            'units' => $validated['units'],
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




public function displayItemsAndStocks(Request $request)
{
    try {

       $query = DB::table('tbl_stocks as s')
    ->join('tbl_item_code as i', 's.ItemCode_id', '=', 'i.ItemCode_id')
    ->leftJoin('tbl_receive_items as ri', 'ri.ItemCode_id', '=', 's.ItemCode_id')
    ->select(
        'i.ItemCode_id as id',
        'i.ItemCode as Material_Code',
        'i.product_name',
        'i.units',
        DB::raw('MAX(ri.units) as product_type'), // just pick one or aggregate
        's.quantity_onhand as Qty'
    )
    ->where('s.quantity_onhand', '>', 0)
    ->groupBy('i.ItemCode_id', 'i.ItemCode','i.units', 'i.product_name', 's.quantity_onhand')
    ->orderBy('i.ItemCode_id', 'asc');


        // Optional: Add pagination
        $perPage = $request->input('per_page', 10);
        $data = $query->paginate($perPage);

        return response()->json([
            'message' => 'Items and Stocks retrieved successfully.',
            'data' => $data
        ], 200);

    } catch (\Exception $e) {

        return response()->json([
            'message' => 'Error retrieving data.',
            'error' => $e->getMessage()
        ], 500);

    }
}


public function GetItemsForMrv()
{
    $items = DB::table('tbl_item_code as i')
        ->leftJoin('tbl_stocks as s', 's.ItemCode_id', '=', 'i.ItemCode_id')
        ->select(
            'i.ItemCode_id as id',
            'i.ItemCode as Material_Code',
            'i.product_name',
            'i.units',
            's.usable_stock as Usable',
            DB::raw('COALESCE(s.quantity_onhand, 0) as quantity_onhand')
        )
        ->orderBy('i.product_name')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $items
    ]);
}


}
