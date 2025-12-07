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
    // VALIDATION
    $request->validate([
        'returned_by' => 'required|string',
        'work_order'  => 'nullable|string',
        'job_order'   => 'nullable|string',

        'items'                 => 'required|array|min:1',
        'items.*.itemcode_id'   => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.returned_qty'  => 'required|integer|min:1',
        'items.*.cost'          => 'required|numeric|min:0',
        'items.*.condition'     => 'required|in:G,U',
    ]);

    DB::beginTransaction();

    try {

        // CREATE HEADER
        $mcrt = ModelMcrt::create([
            'returned_by' => $request->returned_by,
            'received_by' => auth()->user()?->fullname ?? 'system',
            'work_order'  => $request->work_order,
            'job_order'   => $request->job_order,
            'grand_total' => collect($request->items)->sum(fn ($i) => $i['cost'] * $i['returned_qty']),
        ]);

        foreach ($request->items as $row) {

            $itemcodeId  = $row['itemcode_id'];
            $qty         = (int) $row['returned_qty'];
            $cost        = (float) $row['cost'];
            $condition   = $row['condition'];

            // CREATE MCRT ITEM
            ModelMcrtItem::create([
                'mcrt_id'      => $mcrt->mcrt_id,
                'itemcode_id'  => $itemcodeId,
                'returned_qty' => $qty,
                'cost'         => $cost,
                'condition'    => $condition,
            ]);

            // GET STOCK (or create if missing)
            $stock = DB::table('tbl_stocks')->where('ItemCode_id', $itemcodeId)->lockForUpdate()->first();

            if (!$stock) {
                DB::table('tbl_stocks')->insert([
                    'ItemCode_id'     => $itemcodeId,
                    'quantity_onhand' => 0,
                    'usable_stock'    => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $stock = DB::table('tbl_stocks')->where('ItemCode_id', $itemcodeId)->lockForUpdate()->first();
            }

            // UPDATE STOCK
            if ($condition === 'G') {
                // GOOD AS NEW → add to main stock
                DB::table('tbl_stocks')
                    ->where('ItemCode_id', $itemcodeId)
                    ->update([
                        'quantity_onhand' => $stock->quantity_onhand + $qty,
                        'updated_at'      => now(),
                    ]);
            } 
            else {
                // USABLE → add to usable stock
                DB::table('tbl_stocks')
                    ->where('ItemCode_id', $itemcodeId)
                    ->update([
                        'usable_stock' => $stock->usable_stock + $qty,
                        'updated_at'   => now(),
                    ]);
            }

            // CREATE TRANSACTION LOG (ONLY EXISTING FIELDS!)
            ModelTransactions::create([
                'ItemCode_id'      => $itemcodeId,
                'movement_type'    => 'IN',
                'transaction_type' => 'RETURN', // fixed type
                'quantity'         => $qty,
                'remarks'           => $condition,
                'reference'        => $mcrt->mcrt_number,
                'reference_type'   => 'MCRT',
                'user_id'          => auth()->id(),
                'created_by'       => auth()->user()?->fullname ?? 'system',
                'updated_by'       => null,
                'status'           => 'ACTIVE',
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MCRT saved successfully',
            'data'    => $mcrt,
        ]);

    } catch (\Throwable $e) {

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

        'items'                   => 'required|array|min:1',
        'items.*.itemcode_id'     => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.returned_qty'    => 'required|integer|min:1',
        'items.*.cost'            => 'required|numeric|min:0',
        'items.*.condition'       => 'required|in:G,U',
    ]);

    DB::beginTransaction();

    try {

        $mcrt = ModelMcrt::with('items')->findOrFail($mcrt_id);

        // UPDATE HEADER
        $mcrt->update([
            'returned_by' => $request->returned_by,
            'received_by' => $request->received_by,
            'work_order'  => $request->work_order,
            'job_order'   => $request->job_order,
            'grand_total' => collect($request->items)->sum(fn ($i) => $i['cost'] * $i['returned_qty']),
        ]);

        // EXISTING ITEM IDs
        $existingIDs = $mcrt->items->pluck('mcrt_item_id')->toArray();

        foreach ($request->items as $item) {

            $id        = $item['mcrt_item_id'] ?? null;
            $itemId    = $item['itemcode_id'];
            $qtyNew    = $item['returned_qty'];
            $condition = $item['condition'];

            // GET STOCK ROW
            $stock = DB::table('tbl_stocks')
                ->where('ItemCode_id', $itemId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                DB::table('tbl_stocks')->insert([
                    'ItemCode_id'     => $itemId,
                    'quantity_onhand' => 0,
                    'usable_stock'    => 0,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                $stock = DB::table('tbl_stocks')
                    ->where('ItemCode_id', $itemId)
                    ->lockForUpdate()
                    ->first();
            }

            /* ==========================================================
               A. ITEM EXISTS → UPDATE IT
               ========================================================== */
            if ($id && in_array($id, $existingIDs)) {

                $mcrtItem = ModelMcrtItem::find($id);

                $qtyOld = $mcrtItem->returned_qty;
                $condOld = $mcrtItem->condition;

                // REVERSE OLD STOCK
                if ($condOld === 'G') {
                    DB::table('tbl_stocks')->where('ItemCode_id', $itemId)
                        ->update(['quantity_onhand' => $stock->quantity_onhand - $qtyOld]);
                } else {
                    DB::table('tbl_stocks')->where('ItemCode_id', $itemId)
                        ->update(['usable_stock' => $stock->usable_stock - $qtyOld]);
                }

                // UPDATE STOCK ROW AFTER REVERSAL
                $stock = DB::table('tbl_stocks')
                    ->where('ItemCode_id', $itemId)
                    ->lockForUpdate()
                    ->first();

                // APPLY NEW STOCK
                if ($condition === 'G') {
                    DB::table('tbl_stocks')->where('ItemCode_id', $itemId)
                        ->update(['quantity_onhand' => $stock->quantity_onhand + $qtyNew]);
                } else {
                    DB::table('tbl_stocks')->where('ItemCode_id', $itemId)
                        ->update(['usable_stock' => $stock->usable_stock + $qtyNew]);
                }

                // UPDATE TRANSACTION
                ModelTransactions::where([
                    'ItemCode_id'    => $itemId,
                    'reference'      => $mcrt->mcrt_number,
                    'reference_type' => 'MCRT',
                    'movement_type'  => 'IN',
                ])->update([
                    'quantity'   => $qtyNew,
                    'updated_by' => auth()->user()?->fullname ?? 'system',
                    'updated_at' => now(),
                ]);

                // UPDATE ITEM RECORD
                $mcrtItem->update([
                    'returned_qty' => $qtyNew,
                    'cost'         => $item['cost'],
                    'condition'    => $condition,
                ]);
            }

            /* ==========================================================
               B. NEW ITEM ADDED → ADD STOCK + ADD TRANSACTION
               ========================================================== */
            else {

                $mcrtItem = $mcrt->items()->create([
                    'itemcode_id'  => $itemId,
                    'returned_qty' => $qtyNew,
                    'cost'         => $item['cost'],
                    'condition'    => $condition,
                ]);

                // APPLY NEW STOCK
                if ($condition === 'G') {
                    DB::table('tbl_stocks')->where('ItemCode_id', $itemId)
                        ->update(['quantity_onhand' => $stock->quantity_onhand + $qtyNew]);
                } else {
                    DB::table('tbl_stocks')->where('ItemCode_id', $itemId)
                        ->update(['usable_stock' => $stock->usable_stock + $qtyNew]);
                }

                // CREATE NEW TRANSACTION
                ModelTransactions::create([
                    'ItemCode_id'      => $itemId,
                    'movement_type'    => 'IN',
                    'transaction_type' => 'RETURN',
                    'quantity'         => $qtyNew,
                    'reference'        => $mcrt->mcrt_number,
                    'reference_type'   => 'MCRT',
                    'status'           => 'ACTIVE',
                    'user_id'          => auth()->id(),
                    'created_by'       => auth()->user()?->fullname ?? 'system',
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MCRT updated successfully.',
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

            // Lock stock row
            $stock = DB::table('tbl_stocks')
                ->where('ItemCode_id', $item->itemcode_id)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                throw new \Exception("Stock record missing for ItemCode_id {$item->itemcode_id}");
            }

            // Reverse stock based on condition
            if ($item->condition === 'G') {

                // Reverse MAIN STOCK
                $newMain = $stock->quantity_onhand - $item->returned_qty;

                if ($newMain < 0) {
                    throw new \Exception("Stock cannot be negative for ItemCode_id {$item->itemcode_id}");
                }

                DB::table('tbl_stocks')
                    ->where('ItemCode_id', $item->itemcode_id)
                    ->update(['quantity_onhand' => $newMain]);

            } else {

                // Reverse USABLE STOCK
                $newUsable = $stock->usable_stock - $item->returned_qty;

                if ($newUsable < 0) {
                    throw new \Exception("Usable stock cannot be negative for ItemCode_id {$item->itemcode_id}");
                }

                DB::table('tbl_stocks')
                    ->where('ItemCode_id', $item->itemcode_id)
                    ->update(['usable_stock' => $newUsable]);
            }

            // Log reversal in transaction table
            ModelTransactions::create([
                'ItemCode_id'      => $item->itemcode_id,
                'movement_type'    => 'OUT',  // reverse
                'transaction_type' => 'RETURN',
                'quantity'         => $item->returned_qty,
                'reference'        => $mcrt->mcrt_number,
                'reference_type'   => 'MCRT',
                'status'           => 'REVERSED_DELETE',
                'user_id'          => auth()->id(),
                'created_by'       => auth()->user()?->fullname ?? 'system',
                'updated_by'       => null,
            ]);

            // Soft delete item row
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
