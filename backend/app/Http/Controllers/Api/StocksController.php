<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Stocks;
use App\Models\Products;

class StocksController extends Controller
{
    public function CreateProductStocks(Request $request)
    {
        $validated = $request->validate([
            'item_code' => 'required|string|max:50|unique:tbl_products,item_code', 
            'product_name' => 'required|string|max:255', 
            'description' => 'nullable|string',
            'quantity_inStock' => 'required|numeric|min:0',
            'unitPrice' => 'required|numeric|min:0',
            'product_type' => 'nullable|string|max:100',
            'quantity_onhand' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();

        try {
            // 1️⃣ Create product
            $product = Products::create([
                'item_code' => $validated['item_code'],
                'product_name' => $validated['product_name'],
                'description' => $validated['description'] ?? null,
                'quantity_inStock' => $validated['quantity_inStock'],
                'unitPrice' => $validated['unitPrice'],
                'product_type' => $validated['product_type'] ?? null,
            ]);

            // 2️⃣ Create stock (linked to product_id)
            $stock = Stocks::create([
                'product_id' => $product->product_id,
                'quantity_onhand' => $validated['quantity_onhand'],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Product and stock created successfully',
                'product' => $product,
                'stock' => $stock,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create product and stock',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
