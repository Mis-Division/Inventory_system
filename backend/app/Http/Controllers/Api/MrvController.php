<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\ModelMrv;
use App\Models\ModelMrvItems;
use App\Models\RRHistory;
use App\Models\ModelStocks;
use App\Models\ModelTransactions;

class MrvController extends Controller
{
/**
 * CREATE MRV (with negative stock allowed)
 */
public function CreateMrv(Request $request)
{
    // VALIDATION
    $validated = $request->validate([
        'requested_by' => 'required|string|max:255',
        'department'   => 'required|string|max:255',
        'approved_by'  => 'required|string|max:255',
        'usable_only'  => 'nullable|boolean',   // ⬅ not required anymore
        'items'        => 'required|array|min:1',
        'items.*.itemcode_id'   => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.requested_qty' => 'required|integer|min:1',
    ]);

    DB::beginTransaction();

    try {

        // CREATE HEADER
        $createMrv = ModelMrv::create([
            'requested_by' => $validated['requested_by'],
            'department'   => $validated['department'],
            'approved_by'  => $validated['approved_by'],
            'created_by'   => auth()->user()?->fullname ?? 'system',
            'status'       => 'Approved',
        ]);

        // STOCK LOCK
        $itemIds = collect($validated['items'])->pluck('itemcode_id');
        $stocks = DB::table('tbl_stocks')
            ->whereIn('ItemCode_id', $itemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('ItemCode_id');

        $useUsableStock = $validated['usable_only'] ?? false; // ⬅ default false

        foreach ($validated['items'] as $item) {

            $itemId = $item['itemcode_id'];
            $qty    = (int)$item['requested_qty'];

            $stock = $stocks[$itemId];

            $mainStock   = $stock->quantity_onhand ?? 0;
            $usableStock = $stock->usable_stock ?? 0;

            if ($useUsableStock && $usableStock > 0) {

                // 🔥 DEDUCT FROM USABLE
                $newUsable = $usableStock - $qty;

                DB::table('tbl_stocks')
                    ->where('ItemCode_id', $itemId)
                    ->update([
                        'usable_stock' => $newUsable,
                        'updated_at'   => now(),
                    ]);

                $remarks = "U";

            } else {

                // 🔥 DEDUCT FROM MAIN STOCK
                $newMain = $mainStock - $qty;

                DB::table('tbl_stocks')
                    ->where('ItemCode_id', $itemId)
                    ->update([
                        'quantity_onhand' => $newMain,
                        'updated_at'      => now(),
                    ]);

                $remarks = null;
            }

            // INSERT ITEMS
            ModelMrvItems::create([
                'mrv_id'        => $createMrv->mrv_id,
                'itemcode_id'   => $itemId,
                'requested_qty' => $qty,
            ]);

            // INSERT TRANSACTION
            ModelTransactions::create([
                'ItemCode_id'     => $itemId,
                'movement_type'   => 'OUT',
                'transaction_type'=> 'REQUEST',
                'quantity'        => $qty,
                'remarks'         => $remarks, // ⬅ U or null
                'reference'       => $createMrv->mrv_number,
                'reference_type'  => 'MRV',
                'user_id'         => auth()->id(),
                'created_by'      => auth()->user()?->fullname ?? 'system',
                'status'          => 'ACTIVE',
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MRV successfully created.',
            'mrv_id'  => $createMrv->mrv_id,
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'Failed to create MRV!',
            'details' => $e->getMessage(),
        ], 500);
    }
}




/**
 * DISPLAY ALL MRV LIST
 */
public function displayMrv(Request $request)
{
    $queryStr = $request->input('query');
    $perPage  = $request->input('per_page', 10);

    $query = DB::table('tbl_mrv as m')
        ->select(
            'm.mrv_id',
            'm.mrv_number',
            'm.department',
            'm.requested_by',
            'm.approved_by',
            'm.status',
            'm.created_at'
        )
        ->whereNull('m.deleted_at')
        ->when($queryStr, function ($q) use ($queryStr) {
            $q->where(function ($subQ) use ($queryStr) {
                $subQ->where('m.mrv_id', 'like', "%{$queryStr}%")
                    ->orWhere('m.mrv_number', 'like', "%{$queryStr}%")
                    ->orWhere('m.department', 'like', "%{$queryStr}%")
                    ->orWhere('m.requested_by', 'like', "%{$queryStr}%")
                    ->orWhere('m.approved_by', 'like', "%{$queryStr}%")
                    ->orWhere('m.created_at', 'like', "%{$queryStr}%");
            });
        })
        ->orderBy('m.created_at', 'desc');

    $paginated = $query->paginate($perPage);

    return response()->json([
        'success' => true,
        'message' => $paginated->isEmpty()
            ? ($queryStr ? "No MRV matching '{$queryStr}' found!" : "No MRV found.")
            : ($queryStr ? "MRV matching '{$queryStr}' fetched successfully!" : "All MRV fetched successfully."),
        'data' => $paginated->items(),
        'meta' => [
            'current_page' => $paginated->currentPage(),
            'per_page'     => $paginated->perPage(),
            'total'        => $paginated->total(),
            'last_page'    => $paginated->lastPage(),
            'from'         => $paginated->firstItem(),
            'to'           => $paginated->lastItem(),
        ],
    ]);
}



/**
 * GET MRV DETAILS
 */
public function gerMrvDetails($mrv_id)
{
    $mrv = DB::table('tbl_mrv as m')
        ->select(
            'm.mrv_id',
            'm.mrv_number',
            'm.requested_by',
            'm.approved_by',
            'm.created_by',
            'm.created_at',
            'm.status'
        )
        ->where('m.mrv_id', $mrv_id)
        ->whereNull('m.deleted_at')
        ->first();

    if (!$mrv) {
        return response()->json([
            'success' => false,
            'message' => 'MRV not found!',
        ], 404);
    }

    $items = DB::table('tbl_mrv_items as m')
        ->join('tbl_item_code as i', 'i.ItemCode_id', '=', 'm.itemcode_id')
        ->select(
            'i.ItemCode',
            'm.requested_qty',
            'm.product_type'
        )
        ->where('m.mrv_id', $mrv_id)
        ->get();

    return response()->json([
        'success' => true,
        'message' => "MRV ID {$mrv_id} fetched successfully!",
        'data' => [
            'mrv_id'        => $mrv->mrv_id,
            'mrv_number'    => $mrv->mrv_number,
            'requested_by'  => $mrv->requested_by,
            'approved_by'   => $mrv->approved_by,
            'created_by'    => $mrv->created_by,
            'created_at'    => $mrv->created_at,
            'status'        => $mrv->status,
            'items'         => $items,
        ]
    ]);
}

}
