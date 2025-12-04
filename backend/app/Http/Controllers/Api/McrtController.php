<?php

namespace App\Http\Controllers\Api;


use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ModelMcrt;
use App\Models\ModelMcrtItem;
use App\Models\ModelStocks;
use App\Models\ModelTransactions;
use Illuminate\Support\Facades\DB;

class McrtController extends Controller
{
 public function store(Request $request)
{
    $request->validate([
        'returned_by' => 'required|string',
        // ❌ REMOVE 'received_by' — backend auto-sets it
        'work_order'  => 'nullable|string',
        'job_order'   => 'nullable|string',

        'items'                 => 'required|array|min:1',
        'items.*.itemcode_id'   => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.returned_qty'  => 'required|integer|min:1',
        'items.*.cost'          => 'required|numeric|min:0',
        'items.*.condition'     => 'required|in:G,U', // ✔ EXACT match
    ]);

    DB::beginTransaction();

    try {

        // 1️⃣ Create MCRT
        $mcrt = ModelMcrt::create([
            'returned_by' => $request->returned_by,
            'received_by' => auth()->user()?->fullname ?? 'system',
            'work_order'  => $request->work_order ?? null,
            'job_order'   => $request->job_order ?? null,
            'grand_total' => collect($request->items)->sum(function ($i) {
                return $i['cost'] * $i['returned_qty'];
            }),
        ]);

        // 2️⃣ Create Items
        foreach ($request->items as $item) {

            ModelMcrtItem::create([
                'mcrt_id'      => $mcrt->mcrt_id,
                'itemcode_id'  => $item['itemcode_id'],
                'returned_qty' => $item['returned_qty'],
                'cost'         => $item['cost'],
                'condition'    => $item['condition'], // ✔ exact value
            ]);

            // 3️⃣ Update stock
            $affected = DB::table('tbl_stocks')
                ->where('ItemCode_id', $item['itemcode_id'])
                ->increment('quantity_onhand', $item['returned_qty']);
                if (!$affected) {
                    // Auto-create stock entry
                    DB::table('tbl_stocks')->insert([
                        'ItemCode_id' => $item['itemcode_id'],
                        'quantity_onhand' => $item['returned_qty'],
                    ]);

                    // continue process
                    $stockAfter = $item['returned_qty'];
                }


            // Get updated stock for logs
            $stockAfter = DB::table('tbl_stocks')
                ->where('ItemCode_id', $item['itemcode_id'])
                ->value('quantity_onhand');

            // 4️⃣ Add transaction log
            ModelTransactions::create([
                'ItemCode_id'      => $item['itemcode_id'],
                'movement_type'    => 'IN',
                'transaction_type' => 'RETURN',
                'quantity'         => $item['returned_qty'],
                'reference'        => $mcrt->mcrt_number,
                'reference_type'   => 'MCRT',
                'status'           => 'ACTIVE',
                'user_id'          => auth()->id() ?? null,
                'created_by'       => auth()->user()?->fullname ?? 'system',
                'stock_before'     => $stockAfter - $item['returned_qty'],
                'stock_after'      => $stockAfter,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MCRT saved successfully',
            'data'    => $mcrt,
        ]);

    } catch (\Exception $e) {

        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to save MCRT: ' . $e->getMessage(),
        ], 500);
    }
}




public function showMcrt(Request $request)
{
    // Load MCRT with related items
    $items = ModelMcrt::with('items')  // 👈 IMPORTANT
        ->orderBy('created_at', 'desc')
        ->get();

    // Paginate manually
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);

    $currentItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

    $paginated = new LengthAwarePaginator(
        $currentItems,
        $items->count(),
        $perPage,
        $page,
        ['path' => ''] 
    );

    return response()->json([
        'success' => true,
        'data' => $paginated->toArray(),
    ]);
}


public function showMcrtById(Request $request, $mcrt_id)
{
    // Hanapin ang MCRT, auto-exclude soft-deleted dahil may SoftDeletes
    $mcrt = ModelMcrt::with('items') // kasama ang items
        ->where('mcrt_id', $mcrt_id)
        ->first();

    if (!$mcrt) {
        return response()->json([
            'success' => false,
            'message' => 'MCRT not found or has been deleted.'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $mcrt,
    ]);
}


public function updateMcrt(Request $request, $mcrt_id)
{
    $request->validate([
        'returned_by' => 'required|string',
        'received_by' => 'required|string',
        'items'       => 'required|array|min:1',
        'items.*.itemcode_id'  => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.returned_qty' => 'required|integer|min:1',
        'items.*.cost'         => 'required|numeric|min:0',
        'items.*.condition'    => 'required|in:G,U',
    ]);

    DB::beginTransaction();

    try {
        $mcrt = ModelMcrt::findOrFail($mcrt_id);

        // 1️⃣ Update header (MCRT No. does NOT change)
        $mcrt->update([
            'returned_by' => $request->returned_by,
            'received_by' => $request->received_by,
            'work_order'  => $request->work_order ?? null,
            'job_order'   => $request->job_order ?? null,
            'grand_total' => collect($request->items)->sum(function($i){
                return $i['cost'] * $i['returned_qty'];
            }),
        ]);

        // 2️⃣ Existing items in DB
        $existingItemIDs = $mcrt->items()->pluck('mcrt_item_id')->toArray();

        foreach ($request->items as $item) {

            /* ==========================================================
               A. EXISTING ITEM — update row + adjust stock + update transaction
               ========================================================== */
            if (isset($item['mcrt_item_id']) && in_array($item['mcrt_item_id'], $existingItemIDs)) {

                $mcrtItem = $mcrt->items()->find($item['mcrt_item_id']);

                // qty difference
                $diffQty = $item['returned_qty'] - $mcrtItem->returned_qty;

                //  A1. Adjust stock if needed
                if ($diffQty != 0) {
                    DB::table('tbl_stocks')
                        ->where('ItemCode_id', $item['itemcode_id'])
                        ->increment('quantity_onhand', $diffQty);
                }

                // current stock after update
                $stockAfter = DB::table('tbl_stocks')
                    ->where('ItemCode_id', $item['itemcode_id'])
                    ->value('quantity_onhand');

                //  A2. Update Transaction (DO NOT INSERT NEW)
                $transaction = ModelTransactions::where([
                    'ItemCode_id'    => $item['itemcode_id'],
                    'reference'      => $mcrt->mcrt_number,
                    'reference_type' => 'MCRT',
                    'movement_type'  => 'IN',
                ])->first();

                if ($transaction) {
                    // update the existing transaction row
                    $transaction->update([
                        'quantity'     => $item['returned_qty'],
                        'stock_before' => $stockAfter - $item['returned_qty'],
                        'stock_after'  => $stockAfter,
                        'updated_at'   => now(),
                    ]);
                }

                //  A3. Update the item row
                $mcrtItem->update([
                    'returned_qty' => $item['returned_qty'],
                    'cost'         => $item['cost'],
                    'condition'    => $item['condition'],
                ]);

            }

            /* ==========================================================
               B. NEW ITEM — create + adjust stock + create transaction
               ========================================================== */
            else {

                $newItem = $mcrt->items()->create([
                    'itemcode_id'  => $item['itemcode_id'],
                    'returned_qty' => $item['returned_qty'],
                    'cost'         => $item['cost'],
                    'condition'    => $item['condition'],
                ]);

                DB::table('tbl_stocks')
                    ->where('ItemCode_id', $item['itemcode_id'])
                    ->increment('quantity_onhand', $item['returned_qty']);

                // current stock after new add
                $stockAfter = DB::table('tbl_stocks')
                    ->where('ItemCode_id', $item['itemcode_id'])
                    ->value('quantity_onhand');

                // create ONLY because it's a new item
                ModelTransactions::create([
                    'ItemCode_id'      => $item['itemcode_id'],
                    'movement_type'    => 'IN',
                    'transaction_type' => 'RETURN',
                    'quantity'         => $item['returned_qty'],
                    'reference'        => $mcrt->mcrt_number,
                    'reference_type'   => 'MCRT',
                    'status'           => 'ACTIVE',
                    'user_id'          => auth()->id(),
                    'created_by'       => auth()->user()?->fullname ?? 'system',
                    'stock_before'     => $stockAfter - $item['returned_qty'],
                    'stock_after'      => $stockAfter,
                ]);

            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MCRT updated successfully',
            'data'    => $mcrt,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to update MCRT: ' . $e->getMessage(),
        ], 500);
    }
}



public function deleteMcrt($mcrt_id)
{
    DB::beginTransaction();

    try {
        $mcrt = ModelMcrt::with('items')->findOrFail($mcrt_id);

        foreach ($mcrt->items as $item) {

            // Lock stock
            $stockRow = DB::table('tbl_stocks')
                ->where('ItemCode_id', $item->itemcode_id)
                ->lockForUpdate()
                ->first();

            $stockAfter = $stockRow->quantity_onhand - $item->returned_qty;
            $stockBefore = $stockRow->quantity_onhand;

            if ($stockAfter < 0) {
                throw new \Exception("Stock cannot be negative for ItemCode_id {$item->itemcode_id}");
            }

            // Update stock
            DB::table('tbl_stocks')
                ->where('ItemCode_id', $item->itemcode_id)
                ->update(['quantity_onhand' => $stockAfter]);


            ModelTransactions::create([
                'ItemCode_id'      => $item->itemcode_id,
                'movement_type'    => 'OUT',      // reversed
                'transaction_type' => 'RETURN',   // allowed
                'quantity'         => $item->returned_qty,
                'reference'        => $mcrt->mcrt_number,
                'reference_type'   => 'MCRT',
                'status'           => 'REVERSED_DELETE',  // ← KEY
                'user_id'          => auth()->id(),
                'created_by'       => auth()->user()?->fullname ?? 'system',
                'stock_before'     => $stockBefore,
                'stock_after'      => $stockAfter,
            ]);

            // Soft delete item
            $item->delete();
        }

        // Soft delete header
        $mcrt->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MCRT deleted successfully.',
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete MCRT: ' . $e->getMessage(),
        ], 500);
    }
}



}
