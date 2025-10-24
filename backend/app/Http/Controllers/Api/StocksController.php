<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stocks;


class StocksController extends Controller
{
    public function CreateProductStocks(Request $request)
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:50|unique:tbl_stocks,item_code', 
            'product_name' => 'required|string|max:255', 
            'descriptions' => 'nullable|string',
            'quantity_onhand' => 'required|numeric|min:0',
            'quantity_in_stock' => 'required|numeric|min:0',
            'unit_cost' => 'nullable|numeric|min:0',
            'product_type' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Create product
            $stock = Stocks::create([
                'item_code' => $validated['item_code'],
                'product_name' => $validated['product_name'],
                'descriptions' => $validated['descriptions'] ?? null,
                'quantity_onhand' => $validated['quantity_onhand'],
                'quantity_in_stock' => $validated['quantity_in_stock'],
                'unit_cost' => $validated['unit_cost']?? null,
                'product_type' => $validated['product_type'],
            ]);


            DB::commit();

            return response()->json([
                'message' => 'Stock created successfully',
                'stock' => $stock,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getStocks(Request $request)
{
    try {
        $search = $request->query('search');
        $productType = $request->query('product_type');
        $perPage = $request->query('per_page', 5); // Default 5 per page

        $query = Stocks::query();

        // ✅ Allowed product types
        $allowedTypes = ['Line_Hardware', 'Special_Hardware', 'Others'];

        // ✅ Filter by product_type
        if (!empty($productType)) {
            if (!in_array($productType, $allowedTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid product_type. Allowed values: Line_Hardware, Special_Hardware, Others.",
                    'data' => [],
                ], 422);
            }

            $query->where('product_type', $productType);
        }

        // ✅ Optional search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('item_code', 'LIKE', "%{$search}%")
                    ->orWhere('product_name', 'LIKE', "%{$search}%")
                    ->orWhere('descriptions', 'LIKE', "%{$search}%")
                    ->orWhere('product_type', 'LIKE', "%{$search}%");
            });
        }

        // ✅ Paginate
        $items = $query->orderBy('created_at', 'asc')->paginate($perPage);

        // ✅ Custom formatted response
        return response()->json([
            'success' => true,
            'message' => $productType
                ? "Stocks filtered by '{$productType}' fetched successfully"
                : ($search
                    ? "Stocks matching '{$search}' fetched successfully"
                    : "All stocks fetched successfully"),
            'data' => [
                'stocks' => $items->items(),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                    'from' => $items->firstItem(),
                    'to' => $items->lastItem(),
                ]
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch stocks data',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    
    public function getStockById($id)
    {
        try{
            $stocks = Stocks::find($id);

            if(!$stocks){
                return response()->json([
                    'success' => false,
                    'message' => " Stocks not Found",
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => " Stock Successfully Fetch",
                'data' =>$stocks
            ], 200);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => "Failed to Fetch Stocks",
            ], 500);
        }
    }

    public function updateStockById(Request $request, $id)
    {
         DB::beginTransaction();
        try{
                $stocks = Stocks::find($id);

            if (!$stocks) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock not found!'
                ], 404);
            }
                $request->validate([
            'item_code' => 'sometimes|required|string|max:255|unique:tbl_stocks,item_code,' . $id . ',id',
            'product_name' => 'sometimes|required|string|max:255',
            'descriptions' => 'sometimes|required|string|max:255',
            'quantity_onhand' => 'sometimes|nullable|numeric|min:0',
            'quantity_in_stock' => 'sometimes|nullable|numeric|min:0',
            'unit_cost' => 'sometimes|nullable|numeric|min:0',
            'product_type' => 'sometimes|required|string|in:Line_Hardware,Special_Hardware,Others',
                ]);

                $stocks->update($request->only([
                'item_code',
                'product_name',
                'descriptions',
                'quantity_onhand',
                'quantity_in_stock',
                'unit_cost',
                'product_type'
                    ]));
                    DB::commit();
                            return response()->json([
            'success' => true,
            'message' => 'Stock updated successfully',
            'data' => $stocks
        ], 200);
        } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to update stock',
            'error' => $e->getMessage()
        ], 500);
     }
    }
    public function deleteStocksById($id)
{
    DB::beginTransaction();

    try {
        $stocks = Stocks::find($id); 

        if (!$stocks) {
            return response()->json([
                'success' => false,
                'message' => 'Stock not found!'
            ], 404);
        }

        $stocks->delete(); // delete record

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Stock successfully deleted!'
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete stock!',
            'error' => $e->getMessage()
        ], 500);
    }
}

}
  

