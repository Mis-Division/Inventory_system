<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ModelMct;
use App\Models\ModelMctItems;
use App\Models\ModelTransactions;
use Illuminate\Support\Facades\DB;
use App\Models\ModelAccountCodes;
use App\Models\ModelDepreciationProfile;

class MctController extends Controller
{
 public function store(Request $request)
{
    $request->validate([
        'mrv_id'        => 'required|integer',
        'mrv_number'    => 'required|string',
        'product_type'  => 'required|string',

        'requested_by'  => 'required|string',
        'received_by'   => 'required|string',
        'remarks'       => 'nullable|string',

        'items'                         => 'required|array|min:1',
        'items.*.account_code'          => 'required|string',
        'items.*.itemcode_id'           => 'required|integer',
        'items.*.requested_qty'         => 'required|numeric|min:1',
        'items.*.remarks'               => 'nullable|string',
    ]);

    $createdBy = auth()->user()?->fullname ?? 'System';

    return DB::transaction(function () use ($request, $createdBy) {

        // =====================================================
        // 1️⃣ CREATE MCT HEADER (DEFAULT = PENDING)
        // =====================================================
        $mct = ModelMct::create([
            'mrv_id'        => $request->mrv_id,
            'mrv_number'    => $request->mrv_number,
            'product_type'  => $request->product_type,

            'requested_by'  => $request->requested_by,
            'issued_by'     => $createdBy,
            'received_by'   => $request->received_by,
            'remarks'       => $request->remarks,

            'status'        => 'PENDING',
            'grand_total'   => 0,
        ]);

        $grandTotal = 0;

        // =====================================================
        // 2️⃣ LOOP ITEMS (ALWAYS RELEASED)
        // =====================================================
        foreach ($request->items as $item) {

            $itemcode_id   = $item['itemcode_id'];
            $account_code  = $item['account_code'];
            $requested_qty = $item['requested_qty'];
            $itemRemarks   = $item['remarks'] ?? null;

            // -------------------------------------------------
            // 2.1 STOCK CHECK
            // -------------------------------------------------
            $quantity_before = DB::table('tbl_stocks')
                ->where('ItemCode_id', $itemcode_id)
                ->value('quantity_onhand');

            if ($quantity_before === null) {
                throw new \Exception("No stock available for ItemCode ID: {$itemcode_id}");
            }

            if ($quantity_before < $requested_qty) {
                throw new \Exception("Insufficient stock for ItemCode ID: {$itemcode_id}");
            }

            $quantity_after = $quantity_before - $requested_qty;

            // -------------------------------------------------
            // 2.2 WEIGHTED AVERAGE COST
            // -------------------------------------------------
            $rrData = DB::table('tbl_receive_items')
                ->where('ItemCode_id', $itemcode_id)
                ->select(
                    DB::raw('SUM(quantity_received) as total_qty'),
                    DB::raw('SUM(quantity_received * unit_cost) as total_amount')
                )
                ->first();

            if (!$rrData || $rrData->total_qty <= 0) {
                throw new \Exception("No RR data for ItemCode ID: {$itemcode_id}");
            }

            $unitCost    = $rrData->total_amount / $rrData->total_qty;
            $totalAmount = $unitCost * $requested_qty;

            $grandTotal += $totalAmount;

            // -------------------------------------------------
            // 2.3 CREATE MCT ITEM (RELEASED)
            // -------------------------------------------------
            $mctItem = ModelMctItems::create([
                'mct_id'        => $mct->mct_id,
                'account_code'  => $account_code,
                'itemcode_id'   => $itemcode_id,
                'requested_qty' => $requested_qty,
                'unit_cost'     => $unitCost,
                'total_amount'  => $totalAmount,
                'remarks'       => $itemRemarks,
                'status'        => 'RELEASED',
            ]);

            // -------------------------------------------------
            // 2.4 UPDATE STOCK
            // -------------------------------------------------
            DB::table('tbl_stocks')
                ->where('ItemCode_id', $itemcode_id)
                ->update([
                    'quantity_onhand' => $quantity_after
                ]);

            // -------------------------------------------------
            // 2.5 TRANSACTION LEDGER
            // -------------------------------------------------
            ModelTransactions::create([
                'ItemCode_id'      => $itemcode_id,
                'movement_type'    => 'OUT',
                'transaction_type' => 'REQUEST',
                'quantity'         => $requested_qty,
                'reference'        => $mct->mct_number,
                'reference_type'   => 'MCT',
                'user_id'          => auth()->id(),
                'created_by'       => $createdBy,
                'status'           => 'ACTIVE',
                'updated_by'       => auth()->id(),
            ]);

            // -------------------------------------------------
            // 2.6 DEPRECIATION PROFILE (ASSET ONLY)
            // -------------------------------------------------
            $acc = ModelAccountCodes::where('account_code', $account_code)->first();

            if ($acc && $acc->account_type == 1) {

                $percent   = $acc->percent_value;
                $lifespan  = $percent > 0 ? (100 / $percent) : 0;

                $yearlyDep  = $unitCost * 0.10;
                $monthlyDep = $yearlyDep / 12;

                ModelDepreciationProfile::create([
                    'mct_item_id'           => $mctItem->mct_item_id,
                    'itemcode_id'           => $itemcode_id,

                    'account_code'          => $account_code,
                    'account_percent_value' => $acc->percent_value,
                    'lifespan_years'        => round($lifespan, 2),

                    'original_cost'         => $unitCost,
                    'book_value'            => $unitCost,

                    'depreciation_rate'     => 10,
                    'monthly_depreciation'  => round($monthlyDep, 2),

                    'acquisition_date'      => now(),
                    'status'                => 'ACTIVE',
                ]);
            }
        }

        // =====================================================
        // 3️⃣ UPDATE GRAND TOTAL
        // =====================================================
        $mct->update([
            'grand_total' => $grandTotal
        ]);

        // =====================================================
        // 4️⃣ CHECK IF MRV + PRODUCT_TYPE IS FULLY RELEASED
        // =====================================================
        $remaining = DB::table('tbl_mrv_items as mi')
            ->leftJoin(DB::raw("
                (
                    SELECT
                        mci.itemcode_id,
                        SUM(mci.requested_qty) AS released_qty
                    FROM tbl_mct_items mci
                    JOIN tbl_mct mct ON mct.mct_id = mci.mct_id
                    WHERE mct.mrv_id = {$request->mrv_id}
                      AND mct.product_type = '{$request->product_type}'
                      AND mci.status = 'RELEASED'
                    GROUP BY mci.itemcode_id
                ) rel
            "), 'rel.itemcode_id', '=', 'mi.itemcode_id')
            ->where('mi.mrv_id', $request->mrv_id)
            ->where('mi.product_type', $request->product_type)
            ->whereRaw('COALESCE(rel.released_qty, 0) < mi.requested_qty')
            ->count();

        if ($remaining === 0) {
            $mct->update([
                'status' => 'COMPLETED'
            ]);
        }

        // =====================================================
        // 5️⃣ RESPONSE
        // =====================================================
        return response()->json([
            'message' => 'MCT created successfully',
            'mct'     => $mct,
            'items'   => ModelMctItems::where('mct_id', $mct->mct_id)->get()
        ], 201);
    });
}




public function displayMct(Request $request)
{
    $search   = $request->query('search');
    $product_type   = $request->query('product_type'); // ✅ FIXED MODULE FILTER
    $perPage  = $request->query('per_page', 10);

    $query = ModelMct::with([
        'items:mct_id,account_code,itemcode_id,requested_qty,unit_cost,total_amount',
    ])
    ->orderBy('mct_id', 'desc');

    // 🔒 MODULE FILTER (REQUIRED FOR LINE HARDWARE)
    if (!empty($product_type)) {
        $query->where('product_type', $product_type);
    }

    // 🔍 SEARCH FILTER (KEYWORD ONLY)
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('mct_number', 'LIKE', "%{$search}%")
              ->orWhere('mrv_number', 'LIKE', "%{$search}%")
              ->orWhere('status', 'LIKE', "%{$search}%")
              ->orWhere('grand_total', 'LIKE', "%{$search}%");
        });
    }

    $data = $query->paginate($perPage);

    // ✅ CLEAN FINAL OUTPUT
    $final = [];

    foreach ($data->items() as $row) {
        $final[] = [
            'mct_id'        => $row->mct_id,
            'mct_number'    => $row->mct_number,
            'mrv_id'        => $row->mrv_id,
            'mrv_number'    => $row->mrv_number,
            'product_type'        => $row->product_type,

            'requested_by'  => $row->requested_by,
            'issued_by'     => $row->issued_by,
            'received_by'   => $row->received_by,

            'remarks'       => $row->remarks,
            'status'        => $row->status,
            'grand_total'   => $row->grand_total,

            'created_at'    => optional($row->created_at)->format('Y-m-d H:i:s'),

            'items'         => $row->items,
            'items_count'   => $row->items->count(),
        ];
    }

    return response()->json([
        'data'          => $final,
        'current_page'  => $data->currentPage(),
        'per_page'      => $data->perPage(),
        'total'         => $data->total(),
        'last_page'     => $data->lastPage(),
    ]);
}


//check Mrv if completed,exists
public function checkMrvForMct(Request $request)
{
    $request->validate([
        'mrv_number'   => 'required|string',
        'product_type' => 'required|string',
    ]);

    // =====================================================
    // 1️⃣ FETCH MRV
    // =====================================================
    $mrv = DB::table('tbl_mrv')
        ->where('mrv_number', $request->mrv_number)
        ->whereNull('deleted_at')
        ->first();

    if (!$mrv) {
        return response()->json([
            'status'  => 'NOT_FOUND',
            'message' => 'MRV not found.'
        ], 404);
    }

    // =====================================================
    // 2️⃣ STATUS CHECK
    // =====================================================
    if (strtoupper($mrv->status) !== 'APPROVED') {
        return response()->json([
            'status'  => 'NOT_APPROVED',
            'message' => 'MRV is not approved.',
            'current_status' => $mrv->status
        ], 422);
    }

    // =====================================================
    // 3️⃣ FETCH MRV ITEMS (BY PRODUCT TYPE)
    // =====================================================
    $mrvItems = DB::table('tbl_mrv_items')
        ->where('mrv_id', $mrv->mrv_id)
        ->where('product_type', $request->product_type)
        ->whereNull('deleted_at')
        ->get();

    if ($mrvItems->isEmpty()) {
        return response()->json([
            'status'  => 'NO_ITEMS',
            'message' => 'No MRV items for this product type.'
        ], 422);
    }

    // =====================================================
    // 4️⃣ FIND EXISTING MCT HEADER (IF ANY)
    // =====================================================
    $mct = DB::table('tbl_mct')
        ->where('mrv_id', $mrv->mrv_id)
        ->where('product_type', $request->product_type)
        ->whereNotIn('status', ['CANCELLED', 'REVERSED'])
        ->orderByDesc('mct_id')
        ->first();

    $mctId = $mct?->mct_id;

    // =====================================================
    // 5️⃣ CHECK ITEMS ONE BY ONE
    // =====================================================
    $itemsResult  = [];
    $hasRemaining = false;
    $hasReleased  = false;

    foreach ($mrvItems as $mi) {

        $item = DB::table('tbl_item_code')
            ->where('ItemCode_id', $mi->itemcode_id)
            ->first();

        $releasedQty = 0;

        if ($mctId) {
            $releasedQty = DB::table('tbl_mct_items')
                ->where('mct_id', $mctId)
                ->where('itemcode_id', $mi->itemcode_id)
                ->where('status', 'RELEASED')
                ->sum('requested_qty');
        }

        if ($releasedQty > 0) {
            $hasReleased = true;
        }

        $remainingQty = max(
            $mi->requested_qty - $releasedQty,
            0
        );

        if ($remainingQty > 0) {
            $hasRemaining = true;
        }

        $itemsResult[] = [
            'mrv_item_id'   => $mi->mrv_item_id,
            'itemcode_id'   => $mi->itemcode_id,
            'ItemCode'      => $item->ItemCode ?? '',
            'product_name'  => $item->product_name ?? '',
            'requested_qty' => $mi->requested_qty,
            'released_qty'  => $releasedQty,
            'remaining_qty' => $remainingQty,
            'status'        => $remainingQty > 0 ? 'AVAILABLE' : 'COMPLETED'
        ];
    }

    // =====================================================
    // 6️⃣ FINAL DECISION (CRITICAL FIX)
    // =====================================================

    // ✅ COMPLETED only if:
    // - may released data
    // - wala nang remaining
    if ($hasReleased && !$hasRemaining) {
        return response()->json([
            'status'        => 'COMPLETED',
            'message'       => 'All items already released.',
            'mrv_id'        => $mrv->mrv_id,
            'mrv_number'    => $mrv->mrv_number,
            'requested_by'  => $mrv->requested_by,
            'product_type'  => $request->product_type,
            'items'         => $itemsResult
        ]);
    }

    // ✅ DEFAULT & TRUNCATE SAFE FLOW
    return response()->json([
        'status'        => 'AVAILABLE',
        'message'       => 'MRV is available for release.',
        'mrv_id'        => $mrv->mrv_id,
        'mrv_number'    => $mrv->mrv_number,
        'requested_by'  => $mrv->requested_by,
        'product_type'  => $request->product_type,
        'items'         => $itemsResult
    ]);
}



}
