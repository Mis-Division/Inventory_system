<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ModelRfm;
use App\Models\ModelRfmItem;
use App\Models\Item;
use Illuminate\Support\Facades\DB;

class RfmController extends Controller
{
     public function store(Request $request)
    {
        // ==============================
        // 1️⃣ VALIDATION (ALL REQUIRED)
        // ==============================
        $validated = $request->validate([
            // ===== HEADER =====
            'rfm_date' => 'required|date',

            'work_description' => 'required|string',
            'location' => 'required|string',
            'work_type' => 'required|string',

            'primary_lines_retired' => 'required|string',
            'secondary_lines_retired' => 'required|string',
            'cut_of_assembly' => 'required|string',
            'meters' => 'required|string',
            'poles' => 'required|string',
            'busted_transformer' => 'required|string',
            'service_drop_wire' => 'required|string',

            'mco_details' => 'required|string',
            'remarks' => 'required|string',

            'requested_by' => 'required|string',
            'department' => 'required|string',
            'area_engineering' => 'required|string',

            'warehouse_initial' => 'nullable|string',
            'warehouse_date' => 'nullable|date',

            // ===== ITEMS =====
            'items' => 'required|array|min:1',
            'items.*.itemcode_id' => 'required|exists:tbl_item_code,ItemCode_id',
            'items.*.material_description' => 'required|string',
            'items.*.requested_qty' => 'required|string',
            'items.*.remarks' => 'nullable|string',
        ]);

        // ==============================
        // 2️⃣ DUPLICATE ITEM CHECK
        // (PER RFM ONLY)
        // ==============================
        $itemCodes = collect($validated['items'])->pluck('itemcode_id');

        if ($itemCodes->count() !== $itemCodes->unique()->count()) {
            return response()->json([
                'message' => 'Duplicate items are not allowed within the same RFM.',
            ], 422);
        }

        // ==============================
        // 3️⃣ SAVE (TRANSACTION SAFE)
        // ==============================
        try {
            return DB::transaction(function () use ($validated) {

                // ===== CREATE RFM HEADER =====
                $rfm = ModelRfm::create([
                    'rfm_date' => $validated['rfm_date'],

                    'work_description' => $validated['work_description'],
                    'location' => $validated['location'],
                    'work_type' => $validated['work_type'],

                    'primary_lines_retired' => $validated['primary_lines_retired'],
                    'secondary_lines_retired' => $validated['secondary_lines_retired'],
                    'cut_of_assembly' => $validated['cut_of_assembly'],
                    'meters' => $validated['meters'],
                    'poles' => $validated['poles'],
                    'busted_transformer' => $validated['busted_transformer'],
                    'service_drop_wire' => $validated['service_drop_wire'],

                    'mco_details' => $validated['mco_details'],
                    'remarks' => $validated['remarks'],

                    'requested_by' => $validated['requested_by'],
                    'department' => $validated['department'],
                    'area_engineering' => $validated['area_engineering'],

                    'warehouse_initial' => $validated['warehouse_initial'],
                    'warehouse_date' => $validated['warehouse_date'],

                    'created_by' => auth()->user()?->fullname ?? 'System',
                ]);

                // ===== CREATE RFM ITEMS =====
                foreach ($validated['items'] as $item) {
                    ModelRfmItem::create([
                        'rfm_id' => $rfm->rfm_id,
                        'itemcode_id' => $item['itemcode_id'],
                        'material_description' => $item['material_description'],
                        'requested_qty' => $item['requested_qty'],
                        'remarks' => $item['remarks'] ?? null,
                    ]);
                }

                return response()->json([
                    'message' => 'RFM successfully created.',
                    'data' => $rfm->load('items'),
                ], 201);
            });
        } catch (\Illuminate\Database\QueryException $e) {

            // DB-level duplicate protection (if unique index exists)
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'message' => 'Duplicate item detected for this RFM.',
                ], 422);
            }

            throw $e;
        }
    }

   public function index(Request $request)
{
    $search = $request->query('search');
    $user   = auth()->user();

    $query = ModelRfm::with('items')
        ->orderByDesc('rfm_id');

    // ==============================
    // 🔒 SHOW OWN REQUESTS ONLY
    // ==============================
    if (!$user->can('view_all_rfm')) {
        $query->where('created_by', $user->fullname);
    }

    // ==============================
    // 🔍 SEARCH FILTER
    // ==============================
    if (!empty($search)) {
        $query->where(function ($q) use ($search) {
            $q->where('rfm_number', 'LIKE', "%{$search}%")
              ->orWhere('requested_by', 'LIKE', "%{$search}%")
              ->orWhere('department', 'LIKE', "%{$search}%")
              ->orWhere('location', 'LIKE', "%{$search}%")
              ->orWhere('work_description', 'LIKE', "%{$search}%");
        });
    }

    return response()->json(
        $query->paginate(20)
    );
}




public function show($rfm_id)
{
    try {
        // ================= HEADER =================
        $rfm = ModelRfm::where('rfm_id', $rfm_id)->first();

        if (!$rfm) {
            return response()->json([
                'success' => false,
                'message' => 'RFM not found.'
            ], 404);
        }

        // ================= LOCK RULE =================
        if (!empty($rfm->warehouse_initial)) {
            return response()->json([
                'success' => false,
                'message' => 'This RFM has already been processed by the warehouse and can no longer be viewed or updated.'
            ], 403);
        }

        // ================= ITEMS =================
        $items = DB::table('tbl_rfm_items as ri')
            ->join('tbl_item_code as ic', 'ic.ItemCode_id', '=', 'ri.itemcode_id')
            ->select(
                'ri.id',
                'ri.rfm_id',
                'ri.itemcode_id',

                'ic.ItemCode as material_code',
                'ic.units',

                'ri.material_description',
                'ri.requested_qty',
                'ri.remarks'
            )
            ->where('ri.rfm_id', $rfm_id)
            ->whereNull('ri.deleted_at')
            ->get();

        // ================= ATTACH ITEMS =================
        $rfm->items = $items;

        return response()->json([
            'success' => true,
            'data' => $rfm
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch RFM.',
            'error' => $e->getMessage()
        ], 500);
    }
}

// ======== para ito sa MRV =========//
public function fetchRfmForMrv($rfm_number)
{
    try {
        // ================= HEADER =================
        $rfm = ModelRfm::where('rfm_number', $rfm_number)->first();

        if (!$rfm) {
            return response()->json([
                'success' => false,
                'message' => 'RFM not found.'
            ], 404);
        }

        // ================= ITEMS =================
        $items = DB::table('tbl_rfm_items as ri')
            ->join('tbl_item_code as ic', 'ic.ItemCode_id', '=', 'ri.itemcode_id')
            ->leftjoin('tbl_stocks as s', 's.ItemCode_id', '=', 'ic.ItemCode_id')
            ->select(
                'ri.id',
                'ri.rfm_id',
                'ri.itemcode_id',

                'ic.ItemCode as material_code',
                'ic.units',

                'ri.material_description',
                'ri.requested_qty',
                's.quantity_onhand as stocks',
                'ic.item_category as product_type',
                'ri.remarks'
              
            )
            ->where('ri.rfm_id', $rfm->rfm_id)
            ->whereNull('ri.deleted_at')
            ->get();

        // ================= ATTACH ITEMS =================
        $rfm->items = $items;

        return response()->json([
            'success' => true,
            'data' => $rfm
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch RFM for MRV.',
            'error' => $e->getMessage()
        ], 500);
    }
}


public function update(Request $request, $rfm_id)
{
    // ==============================
    // 1️⃣ FIND RFM
    // ==============================
    $rfm = ModelRfm::findOrFail($rfm_id);

    // ==============================
    // 🔒 OWNER CHECK (IMPORTANT)
    // ==============================
    $user = auth()->user();

    if (
        !$user->can('update_all_rfm') &&   // optional permission
        $rfm->created_by !== $user->fullname
    ) {
        return response()->json([
            'success' => false,
            'message' => 'You are not authorized to update this RFM.'
        ], 403);
    }

    // ==============================
    // 2️⃣ VALIDATION
    // ==============================
    $validated = $request->validate([
        // ===== HEADER =====
        'rfm_date' => 'required|date',
        'work_description' => 'required|string',
        'location' => 'required|string',
        'work_type' => 'required|string',

        'primary_lines_retired' => 'required|string',
        'secondary_lines_retired' => 'required|string',
        'cut_of_assembly' => 'required|string',
        'meters' => 'required|string',
        'poles' => 'required|string',
        'busted_transformer' => 'required|string',
        'service_drop_wire' => 'required|string',

        'mco_details' => 'required|string',
        'remarks' => 'required|string',

        'requested_by' => 'required|string',
        'department' => 'required|string',
        'area_engineering' => 'required|string',

        // ===== ITEMS =====
        'items' => 'required|array|min:1',
        'items.*.itemcode_id' => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.material_description' => 'required|string',
        'items.*.requested_qty' => 'required|numeric|min:1',
        'items.*.remarks' => 'nullable|string',
    ]);

    // ==============================
    // 3️⃣ DUPLICATE ITEM CHECK
    // ==============================
    $itemCodes = collect($validated['items'])->pluck('itemcode_id');

    if ($itemCodes->count() !== $itemCodes->unique()->count()) {
        return response()->json([
            'success' => false,
            'message' => 'Duplicate items are not allowed within the same RFM.'
        ], 422);
    }

    // ==============================
    // 4️⃣ TRANSACTION
    // ==============================
    return DB::transaction(function () use ($rfm, $validated) {

        /* ===============================
         * UPDATE HEADER
         * =============================== */
        $rfm->update([
            'rfm_date' => $validated['rfm_date'],
            'work_description' => $validated['work_description'],
            'location' => $validated['location'],
            'work_type' => $validated['work_type'],

            'primary_lines_retired' => $validated['primary_lines_retired'],
            'secondary_lines_retired' => $validated['secondary_lines_retired'],
            'cut_of_assembly' => $validated['cut_of_assembly'],
            'meters' => $validated['meters'],
            'poles' => $validated['poles'],
            'busted_transformer' => $validated['busted_transformer'],
            'service_drop_wire' => $validated['service_drop_wire'],

            'mco_details' => $validated['mco_details'],
            'remarks' => $validated['remarks'],

            'requested_by' => $validated['requested_by'],
            'department' => $validated['department'],
            'area_engineering' => $validated['area_engineering'],
        ]);

        /* ===============================
         * ITEM SYNC (NO DOUBLE INSERT)
         * =============================== */
        $existingItems = ModelRfmItem::where('rfm_id', $rfm->rfm_id)
            ->get()
            ->keyBy('itemcode_id');

        $incomingItemCodes = collect($validated['items'])
            ->pluck('itemcode_id')
            ->toArray();

        // 🔴 SOFT DELETE REMOVED ITEMS
        foreach ($existingItems as $itemcodeId => $existing) {
            if (!in_array($itemcodeId, $incomingItemCodes)) {
                $existing->delete();
            }
        }

        // 🟢 UPDATE OR INSERT
        foreach ($validated['items'] as $item) {
            if ($existingItems->has($item['itemcode_id'])) {
                $existingItems[$item['itemcode_id']]->update([
                    'material_description' => $item['material_description'],
                    'requested_qty' => $item['requested_qty'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            } else {
                ModelRfmItem::create([
                    'rfm_id' => $rfm->rfm_id,
                    'itemcode_id' => $item['itemcode_id'],
                    'material_description' => $item['material_description'],
                    'requested_qty' => $item['requested_qty'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'RFM successfully updated.',
            'data' => $rfm->load('items'),
        ]);
    });
}

public function destroy(Request $request, $rfm_id)
    {
            $validated = $request->validate([
                'delete_remarks' => 'required|string|min:5|max:255',
            ],[
                'delete_remarks.required' => 'Delete Remarks is required!',
            ]); 
        DB::beginTransaction();
            try{
                $rfm = ModelRfm::with('items')
                        ->where('rfm_id', $rfm_id)
                        ->firstOrFail();
                if(!empty($rfm->warehouse_initial)){
                    return response->json([
                        'success' => false,
                        'message' => 'RFM with assigned warehouse date cannot be deleted!',
                    ], 403);
                    }
                    
                    $rfm->delete_remarks =$validated['delete_remarks'];
                    $rfm->deleted_by = auth()->user()?->fullname ?? 'System';
                    $rfm->save();

                    $rfm->delete();
                    DB::commit();
                    return response->json([
                        'success' => true,
                        'message' => 'RFM successfully deleted!',
                    ], 200);
            }catch(\Exception $e){
                DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'failed to delete RFM!',
                'error' => $e->getMessage(),
            ], 500);
            }
    }
public function print($rfm_id)
{
    try {
        // HEADER (NO LOCK CHECK)
        $rfm = ModelRfm::where('rfm_id', $rfm_id)->first();

        if (!$rfm) {
            return response()->json([
                'success' => false,
                'message' => 'RFM not found.'
            ], 404);
        }

        // ITEMS
        $items = DB::table('tbl_rfm_items as ri')
            ->join('tbl_item_code as ic', 'ic.ItemCode_id', '=', 'ri.itemcode_id')
            ->select(
                'ri.id',
                'ri.rfm_id',
                'ic.ItemCode as material_code',
                'ic.units',
                'ri.material_description',
                'ri.requested_qty',
                'ri.remarks'
            )
            ->where('ri.rfm_id', $rfm_id)
            ->whereNull('ri.deleted_at')
            ->get();

        $rfm->items = $items;

        return response()->json([
            'success' => true,
            'data' => $rfm
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch RFM for printing.',
            'error' => $e->getMessage()
        ], 500);
    }
}


}
