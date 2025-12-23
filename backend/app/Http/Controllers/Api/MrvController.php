<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\ModelMrv;
use App\Models\ModelMrvItems;
use App\Models\ModelRfm;
use App\Models\ModelRfmItem;




class MrvController extends Controller
{
/**
 * CREATE MRV (with negative stock allowed)
 */
public function CreateMrv(Request $request)
{
    $validated = $request->validate([
        'rfm_number'   => 'required|string|exists:tbl_rfm,rfm_number',
        'requested_by' => 'required|string|max:255',
        'department'   => 'required|string|max:255',
        'approved_by'  => 'required|string|max:255',

        'items'                  => 'required|array|min:1',
        'items.*.itemcode_id'    => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.product_type'   => 'required|string|max:255',
        'items.*.issued_qty'     => 'required|integer|min:0',
        'items.*.remarks'        => 'nullable|string|max:255',
    ]);

    DB::beginTransaction();

    try {

        // 1️⃣ GET RFM
        $rfm = ModelRfm::where('rfm_number', $validated['rfm_number'])
            ->whereNull('deleted_at')
            ->firstOrFail();

        // 2️⃣ CREATE MRV (default APPROVED)
        $mrv = ModelMrv::create([
            'mrv_date'     => now(),
            'rfm_id'       => $rfm->rfm_id,
            'rfm_number'   => $rfm->rfm_number,
            'requested_by' => $validated['requested_by'],
            'department'   => $validated['department'],
            'approved_by'  => $validated['approved_by'],
            'approved_by_Gm' => 'Glen Mark F. Aquino, CPA',
            'created_by'   => auth()->user()?->fullname ?? 'system',
            'status'       => 'APPROVED',
        ]);

        // 3️⃣ RFM ITEMS LOOKUP (ACTIVE ONLY)
        $rfmItems = ModelRfmItem::where('rfm_id', $rfm->rfm_id)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('itemcode_id');

        $submittedItemCodes = collect($validated['items'])->pluck('itemcode_id');

        // 4️⃣ MARK REMOVED RFM ITEMS
        foreach ($rfmItems as $itemcodeId => $rfmItem) {
            if (!$submittedItemCodes->contains($itemcodeId)) {
                $rfmItem->update([
                    'status'  => 'REMOVED',
                    'remarks' => 'Removed by warehouse',
                ]);
            }
        }

        // 5️⃣ PROCESS MRV ITEMS
        foreach ($validated['items'] as $item) {

            $rfmItem      = $rfmItems->get($item['itemcode_id']);
            $requestedQty = $rfmItem?->requested_qty ?? 0;
            $issuedQty    = (int) $item['issued_qty'];

            if ($rfmItem && $issuedQty > $requestedQty) {
                throw new \Exception('Issued quantity cannot exceed requested quantity.');
            }

            // DEFAULTS
            $itemStatus  = 'APPROVED';
            $productType = $rfmItem->product_type ?? $item['product_type'];

            if (!$productType) {
                throw new \Exception(
                    "Product type missing for itemcode_id {$item['itemcode_id']}"
                );
            }

            // PARTIAL LOGIC
            if ($rfmItem && $issuedQty < $requestedQty) {

                if (empty($item['remarks'])) {
                    throw new \Exception('Remarks required for partial issuance.');
                }

                $itemStatus = 'PARTIAL';

                $rfmItem->update([
                    'status'  => 'PARTIAL',
                    'remarks' => $item['remarks'],
                ]);

            } elseif ($rfmItem) {

                $rfmItem->update([
                    'status' => 'APPROVED',
                ]);
            }

            ModelMrvItems::create([
                'mrv_id'        => $mrv->mrv_id,
                'itemcode_id'   => $item['itemcode_id'],
                'product_type'  => $productType,
                'requested_qty' => $requestedQty,
                'issued_qty'    => $issuedQty,
                'status'        => $itemStatus,
                'remarks'       => $item['remarks'] ?? null,
            ]);
        }

        // 6️⃣ RECOMPUTE MRV STATUS (🔥 SOFT DELETE SAFE)
        $hasPartial = ModelMrvItems::where('mrv_id', $mrv->mrv_id)
            ->whereNull('deleted_at')   // ✅ ignore soft-deleted
            ->where('status', 'PARTIAL')
            ->exists();

        $mrv->update([
            'status' => $hasPartial ? 'PARTIAL' : 'APPROVED',
        ]);

        // 7️⃣ LOCK RFM (WAREHOUSE SIDE)
        $rfm->update([
            'warehouse_initial' => auth()->user()?->fullname ?? 'system',
            'warehouse_date'    => now(),
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MRV successfully created.',
            'data' => [
                'mrv_id'     => $mrv->mrv_id,
                'mrv_number' => $mrv->mrv_number,
                'status'     => $mrv->status,
            ],
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 422);
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
            'm.mrv_date',
            'm.rfm_id',
            'm.rfm_number',

            'm.department',
            'm.requested_by',
            'm.approved_by',
            'm.created_by',
            'm.date_release',
            'm.incharge',
            'm.status',

            'm.created_at'
        )
        ->whereNull('m.deleted_at')

        // ================= SEARCH =================
        ->when($queryStr, function ($q) use ($queryStr) {
            $q->where(function ($subQ) use ($queryStr) {
                $subQ->where('m.mrv_id', 'like', "%{$queryStr}%")
                    ->orWhere('m.mrv_number', 'like', "%{$queryStr}%")
                    ->orWhere('m.rfm_number', 'like', "%{$queryStr}%")
                    ->orWhere('m.department', 'like', "%{$queryStr}%")
                    ->orWhere('m.requested_by', 'like', "%{$queryStr}%")
                    ->orWhere('m.approved_by', 'like', "%{$queryStr}%")
                    ->orWhere('m.created_by', 'like', "%{$queryStr}%")
                    ->orWhere('m.mrv_date', 'like', "%{$queryStr}%")
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
public function MrvDisplaytest(Request $request)
{
    $mrvNumber  = $request->query('mrv_number');
    $productType = $request->query('product_type'); // Line Hardware / SpecialHardware / MotorPool

    if (!$mrvNumber || !$productType) {
        return response()->json([
            'success' => false,
            'message' => 'MRV number and product type are required.'
        ], 422);
    }

    // ===============================
    // 1️⃣ FETCH MRV BY NUMBER
    // ===============================
    $mrv = DB::table('tbl_mrv')
        ->select(
            'mrv_id',
            'mrv_number',
            'requested_by',
            'approved_by',
            'created_by',
            'created_at',
            'status'
        )
        ->where('mrv_number', $mrvNumber)
        ->whereNull('deleted_at')
        ->first();

    if (!$mrv) {
        return response()->json([
            'success' => false,
            'message' => 'MRV not found.'
        ], 404);
    }

    // 🔥 STATUS CHECK
    if (strtoupper($mrv->status) !== 'APPROVED') {
        return response()->json([
            'success' => false,
            'message' => 'MRV is not yet approved.',
            'current_status' => $mrv->status
        ], 403);
    }

    // ===============================
    // 2️⃣ FETCH ITEMS BY PRODUCT TYPE
    // ===============================
    $items = DB::table('tbl_mrv_items as mi')
        ->join('tbl_item_code as ic', 'ic.ItemCode_id', '=', 'mi.itemcode_id')
        ->select(
            'mi.mrv_item_id',
            'mi.itemcode_id',
            'ic.ItemCode',
            'ic.product_name',
            'mi.requested_qty',
            'mi.product_type'
        )
        ->where('mi.mrv_id', $mrv->mrv_id)
        ->where('mi.product_type', $productType)
        ->whereNull('mi.deleted_at')
        ->get();

    if ($items->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No MRV items found for this product type.'
        ], 404);
    }

    // ===============================
    // 3️⃣ RESPONSE
    // ===============================
    return response()->json([
        'success' => true,
        'message' => 'MRV fetched successfully.',
        'data' => [
            'mrv_id'       => $mrv->mrv_id,
            'mrv_number'   => $mrv->mrv_number,
            'requested_by' => $mrv->requested_by,
            'approved_by'  => $mrv->approved_by,
            'created_at'   => $mrv->created_at,
            'status'       => $mrv->status,
            'product_type' => $productType,
            'items'        => $items
        ]
    ]);
}

public function checkMrvByRfm($rfm_number)
{
    try {

        // kahit soft-deleted kukunin natin for checking
        $mrv = DB::table('tbl_mrv')
            ->where('rfm_number', $rfm_number)
            ->first();

        // 🟢 WALANG MRV AT ALL
        if (!$mrv) {
            return response()->json([
                'exists'          => false,
                'status'          => null,
                'blocked_add'     => false,
                'blocked_delete'  => false,
            ], 200);
        }

        // ================= DELETE BLOCK RULES =================
        $blockedDelete = false;

        // ❌ soft deleted na
        if (!is_null($mrv->deleted_at)) {
            $blockedDelete = true;
        }

        // ❌ approved
        if (strtoupper($mrv->status) === 'APPROVED') {
            $blockedDelete = true;
        }

        // ❌ may incharge
        if (!empty($mrv->incharge)) {
            $blockedDelete = true;
        }

        // 🚫 MAY MRV NA → BLOCK ADD
        return response()->json([
            'exists'          => true,
            'status'          => strtoupper($mrv->status),
            'blocked_add'     => true,
            'blocked_delete'  => $blockedDelete,
            'message'         => 'MRV already exists for this RFM#.',
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}


public function showById($mrv_id)
{
    try {

        // ===============================
        // 1️⃣ MRV HEADER
        // ===============================
        $mrv = DB::table('tbl_mrv')
            ->where('mrv_id', $mrv_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$mrv) {
            return response()->json([
                'success' => false,
                'message' => 'MRV not found.'
            ], 404);
        }

        // =========================================
        // 2️⃣ MRV ITEMS (PARTIAL + APPROVED) + RFM REMARKS
        // =========================================
        $items = DB::table('tbl_mrv_items as mi')
            ->join('tbl_item_code as ic', 'mi.itemcode_id', '=', 'ic.ItemCode_id')

            // 🔗 JOIN RFM ITEMS FOR REMARKS
            ->leftJoin('tbl_rfm_items as ri', function ($join) use ($mrv) {
                $join->on('ri.itemcode_id', '=', 'mi.itemcode_id')
                     ->where('ri.rfm_id', '=', $mrv->rfm_id)
                     ->whereNull('ri.deleted_at');
            })

            ->leftJoin('tbl_stocks as s', 'mi.itemcode_id', '=', 's.itemcode_id')

            ->where('mi.mrv_id', $mrv_id)
            ->whereIn('mi.status', ['PARTIAL', 'APPROVED']) // ✅ UPDATED
            ->whereNull('mi.deleted_at')

            ->select(
                'mi.mrv_item_id',
                'mi.itemcode_id',
                'ic.ItemCode as material_code',
                'ic.description as material_description',
                'mi.product_type',
                'mi.requested_qty',
                'mi.issued_qty',
                'mi.status',

                // ✅ remarks from RFM
                DB::raw('COALESCE(ri.remarks, "") as remarks'),

                // stock + units
                DB::raw('COALESCE(s.quantity_onhand, 0) as stocks'),
                'ic.units'
            )
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'header' => $mrv,
                'items'  => $items
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


public function update(Request $request, $mrv_id)
{
    $request->validate([
        'items'                     => 'required|array|min:1',
        'items.*.mrv_item_id'       => 'required|integer|exists:tbl_mrv_items,mrv_item_id',
        'items.*.issued_qty'        => 'required|numeric|min:0',
        'items.*.remarks'           => 'nullable|string|max:255',
    ]);

    DB::beginTransaction();

    try {

        // ===============================
        // 1️⃣ GET MRV HEADER
        // ===============================
        $mrv = DB::table('tbl_mrv')
            ->where('mrv_id', $mrv_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$mrv) {
            throw new \Exception('MRV not found.');
        }

        $rfm_id = $mrv->rfm_id;

        // ===============================
        // 2️⃣ UPDATE MRV ITEMS + RFM ITEMS
        // ===============================
        foreach ($request->items as $item) {

            $mrvItem = DB::table('tbl_mrv_items')
                ->where('mrv_item_id', $item['mrv_item_id'])
                ->whereNull('deleted_at')
                ->first();

            if (!$mrvItem) {
                throw new \Exception('MRV item not found.');
            }

            // 🔥 STATUS LOGIC
            $itemStatus = (
                $item['issued_qty'] >= $mrvItem->requested_qty
            ) ? 'APPROVED' : 'PARTIAL';

            // ===============================
            // UPDATE MRV ITEM
            // ===============================
            DB::table('tbl_mrv_items')
                ->where('mrv_item_id', $item['mrv_item_id'])
                ->update([
                    'issued_qty' => $item['issued_qty'],
                    'status'     => $itemStatus,
                    'updated_at' => now(),
                ]);

            // ===============================
            // UPDATE RFM ITEM (remarks + status)
            // ===============================
            DB::table('tbl_rfm_items')
                ->where('rfm_id', $rfm_id)
                ->where('itemcode_id', $mrvItem->itemcode_id)
                ->whereNull('deleted_at')
                ->update([
                    'remarks'    => $item['remarks'] ?? null,
                    'status'     => $itemStatus,
                    'updated_at' => now(),
                ]);
        }

        // ===============================
        // 3️⃣ CHECK IF ALL MRV ITEMS APPROVED
        // ===============================
        $hasPending = DB::table('tbl_mrv_items')
            ->where('mrv_id', $mrv_id)
            ->where('status', '!=', 'APPROVED')
            ->whereNull('deleted_at')
            ->exists();

        // ===============================
        // 4️⃣ UPDATE MRV HEADER STATUS
        // ===============================
        DB::table('tbl_mrv')
            ->where('mrv_id', $mrv_id)
            ->update([
                'status'     => $hasPending ? 'PARTIAL' : 'APPROVED',
                'updated_at' => now(),
            ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MRV successfully updated.',
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

public function deleteMrv(Request $request, $mrv_id)
{
    // ================= VALIDATION =================
    $validated = $request->validate([
        'remarks' => 'required|string|min:5|max:255',
    ], [
        'remarks.required' => 'Delete remarks is required.',
    ]);

    DB::beginTransaction();

    try {
        // ================= FETCH MRV =================
        $mrv = ModelMrv::with('items')
            ->where('mrv_id', $mrv_id)
            ->firstOrFail();

        // ================= BLOCK IF INCHARGE EXISTS =================
        if (!empty($mrv->incharge)) {
            return response()->json([
                'success' => false,
                'message' => 'MRV with assigned in-charge cannot be deleted.'
            ], 403);
        }

        // ================= SAVE REMARKS =================
        $mrv->remarks = $validated['remarks'];
        $mrv->deleted_by = auth()->user()?->fullname ?? 'system';
        $mrv->save();

        // ================= SOFT DELETE ITEMS =================
        $mrv->items()->delete();

        // ================= SOFT DELETE HEADER =================
        $mrv->delete();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MRV successfully deleted.',
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete MRV.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}
public function printMrv($mrv_id)
{
    try {
        $mrv = ModelMrv::where('mrv_id', $mrv_id)
            ->whereNull('deleted_at')
            ->first();

        if (!$mrv) {
            return response()->json([
                'success' => false,
                'message' => 'MRV not found.',
            ], 404);
        }

        $items = DB::table('tbl_mrv_items as mi')
            ->join('tbl_item_code as ic', 'ic.ItemCode_id', '=', 'mi.itemcode_id')
            ->select(
                'mi.mrv_item_id',
                'ic.ItemCode as material_code',
                'ic.description as material_description',
                'mi.issued_qty as quantity',
                'mi.status as remarks'
            )
            ->where('mi.mrv_id', $mrv_id)
            ->whereNull('mi.deleted_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'header' => $mrv,
                'items'  => $items
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to print MRV.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


}
