<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

use App\Models\ModelReceived;
use App\Models\ModelReceivedItem;
use App\Models\ModelStocks;
use App\Models\RRHistory;
use App\Models\Suppliers;
use App\Models\ModelTransactions;

class ReceivingController extends Controller
{
    // -------------------------
    // STORE RECEIVING REPORT
    // -------------------------
 public function store(Request $request)
{
    $validated = $request->validate([
        'po_number'          => 'nullable|string|max:255',
        'invoice_number'     => 'required|string|max:255',
        'supplier_id'        => 'nullable|exists:tbl_suppliers,supplier_id',
        'dr_number'          => 'required|string|max:255',
        'received_by'        => 'required|string|max:255',
        'receive_date'       => 'required|date',
        'remarks'            => 'nullable|string|max:255',
        'items'              => 'required|array|min:1',
        'items.*.ItemCode_id'        => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.quantity_received'  => 'required|integer|min:1',
        'items.*.unit_cost'          => 'required|numeric|min:0',
        'items.*.quantity_order'     => 'nullable|integer|min:0',
        'items.*.units'              => 'required|string|max:255',
    ]);

    DB::beginTransaction();

    try {
        // Check for duplicate invoice or DR
        if (ModelReceived::where('invoice_number', $request->invoice_number)->exists()) {
            return response()->json(['error' => 'Invoice number already exists.'], 422);
        }
        if (ModelReceived::where('dr_number', $request->dr_number)->exists()) {
            return response()->json(['error' => 'DR number already exists.'], 422);
        }

        // Create Receiving record
        $received = ModelReceived::create([
            'po_number'      => $request->po_number,
            'invoice_number' => $request->invoice_number,
            'supplier_id'    => $request->supplier_id,
            'dr_number'      => $request->dr_number,
            'received_by'    => $request->received_by,
            'receive_date'   => $request->receive_date,
            'remarks'        => 'Pending',
            'grand_total'    => 0,
        ]);

        $grandTotal = 0;
        $itemsData  = [];

        foreach ($request->items as $item) {
            $quantityReceived = (int)($item['quantity_received'] ?? 0);
            $unitCost         = (float)($item['unit_cost'] ?? 0);
            $totalCost        = $quantityReceived * $unitCost;

            // Get original order
            $originalOrder = isset($item['quantity_order'])
                ? (int)$item['quantity_order']
                : ModelReceivedItem::whereHas('received', fn($q) => $q->where('po_number', $request->po_number))
                    ->where('ItemCode_id', $item['ItemCode_id'])
                    ->value('quantity_order') ?? 0;

            // Total previously received
            $totalPreviouslyReceived = ModelReceivedItem::whereHas('received', fn($q) => $q->where('po_number', $request->po_number))
                ->where('ItemCode_id', $item['ItemCode_id'])
                ->sum('quantity_received');

            $totalAfterThisReceive = $totalPreviouslyReceived + $quantityReceived;

            // Rule: over-receiving
            if ($originalOrder > 0 && $totalAfterThisReceive > $originalOrder) {
                return response()->json([
                    'error' => "Receiving this item exceeds ordered quantity. Ordered: {$originalOrder}, Already Received: {$totalPreviouslyReceived}, Attempting to receive: {$quantityReceived}."
                ], 422);
            }

            // Rule: already complete
            if ($originalOrder > 0 && $totalPreviouslyReceived >= $originalOrder) {
                return response()->json([
                    'error' => "Item '{$item['ItemCode_id']}' is already fully received. Cannot add more."
                ], 422);
            }

            // Determine status
            $status = ($originalOrder > 0 && $totalAfterThisReceive == $originalOrder) ? 'Complete' : 'Partial';

            // Create received item
            $receivedItem = ModelReceivedItem::create([
                'r_id'              => $received->r_id,
                'ItemCode_id'       => $item['ItemCode_id'],
                'quantity_order'    => $originalOrder,
                'quantity_received' => $quantityReceived,
                'unit_cost'         => $unitCost,
                'total_cost'        => $totalCost,
                'units'             => $item['units'] ?? null,
                'status'            => $status,
            ]);

            $itemsData[] = $receivedItem;

            // ==============================
            // STOCK UPDATE (AUTO-OFFSET NEGATIVE)
            // ==============================
            $stockRow = DB::table('tbl_stocks')
                ->where('ItemCode_id', $item['ItemCode_id'])
                ->lockForUpdate()
                ->first();

            $currentStock = $stockRow ? $stockRow->quantity_onhand : 0;
            $newStock     = $currentStock + $quantityReceived;

            DB::table('tbl_stocks')
                ->updateOrInsert(
                    ['ItemCode_id' => $item['ItemCode_id']],
                    [
                        'quantity_onhand' => $newStock,
                        'updated_at'      => now(),
                    ]
                );

            // created_by safe fallback
            $createdBy = auth()->user()?->fullname ?? 'System';

            // ==============================
            // TRANSACTION LOG (RR IN)
            // ==============================
            ModelTransactions::create([
                'ItemCode_id'      => $item['ItemCode_id'],
                'movement_type'    => 'IN',
                'transaction_type' => 'Received',  // must match ENUM exactly
                'quantity'         => $quantityReceived,
                'reference'        => $received->rr_number,
                'reference_type'   => 'RR #',
                'user_id'          => auth()->id() ?? null,
                'created_by'       => $createdBy,
                'status'           => 'ACTIVE',    // must match ENUM exactly
            ]);

            $grandTotal += $totalCost;

            // Update previous partial items if now complete
            if ($status === 'Complete') {
                ModelReceivedItem::whereHas('received', fn($q) => $q->where('po_number', $request->po_number))
                    ->where('ItemCode_id', $item['ItemCode_id'])
                    ->update(['status' => 'Complete']);
            }
        }

        // Final status for RR
        $finalStatus = collect($itemsData)->every(fn($i) => $i->status === 'Complete') ? 'Complete' : 'Partial';

        $received->update([
            'grand_total' => $grandTotal,
            'remarks'     => $finalStatus,
        ]);

        // Log history
        RRHistory::create([
            'r_id'     => $received->r_id,
            'user_id'  => auth()->id() ?? 0,
            'old_data' => null,
            'new_data' => [
                'rr'    => $received->fresh(),
                'items' => $itemsData
            ],
            'action' => 'create',
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Receiving record saved successfully.',
            'data'    => [
                'receive' => $received,
                'items'   => $itemsData,
            ]
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'error'   => 'Failed to save receiving record.',
            'details' => $e->getMessage(),
        ], 500);
    }
}




    // -------------------------
    // DISPLAY RECEIVING REPORTS
    // -------------------------

 

public function DisplayRR(Request $request)
{
    $queryStr = $request->input('query');
    $perPage = $request->input('per_page', 1000); // default per batch
    $currentPage = $request->input('page', 1);

    $query = DB::table('tbl_receive as r')
        ->join('tbl_suppliers as s', 'r.supplier_id', '=', 's.supplier_id')
        ->select(
            'r.r_id',
            'r.rr_number',
            'r.po_number',
            'r.dr_number',
            'r.invoice_number',
            's.supplier_name',
            'r.receive_date',
            'r.remarks'
        )
        ->whereNull('r.deleted_at')
        ->when($queryStr, function ($q) use ($queryStr) {
            $q->where(function ($subQ) use ($queryStr) {
                $subQ->where('r.rr_number', 'like', "%{$queryStr}%")
                     ->orWhere('r.po_number', 'like', "%{$queryStr}%")
                     ->orWhere('r.dr_number', 'like', "%{$queryStr}%")
                     ->orWhere('r.invoice_number', 'like', "%{$queryStr}%")
                     ->orWhere('r.remarks', 'like', "%{$queryStr}%")
                     ->orWhere('s.supplier_name', 'like', "%{$queryStr}%");
            });
        })
        ->orderBy('r.rr_number', 'desc');

    // Paginate at database level (more efficient)
    $paginated = $query->paginate($perPage, ['*'], 'page', $currentPage);

    if ($paginated->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => $queryStr 
                ? "No receiving reports matching '{$queryStr}' found." 
                : 'No receiving reports found.',
            'data' => [],
            'meta' => null,
        ], 200);
    }

    // ✅ Compute po_status per PO#
    $poStatusMap = [];
    $items = $paginated->items();
    foreach ($items as $rr) {
        if (!isset($poStatusMap[$rr->po_number])) {
            $samePO = array_filter($items, fn($r) => $r->po_number === $rr->po_number);
            $poStatusMap[$rr->po_number] = collect($samePO)->every(fn($r) => $r->remarks === 'Complete') 
                ? 'Complete' : 'Partial';
        }
        $rr->po_status = $poStatusMap[$rr->po_number];
    }

    return response()->json([
        'success' => true,
        'message' => $queryStr 
            ? "Receiving reports matching '{$queryStr}' fetched successfully." 
            : 'All receiving reports fetched successfully.',
        'data' => $items,
        'meta' => [
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
        ],
    ]);
}



    // -------------------------
    // GET RR BY ID
    // -------------------------
  public function getRRbyId($r_id)
{
    // Fetch main RR info
    $rr = DB::table('tbl_receive as r')
        ->leftJoin('tbl_suppliers as s', 'r.supplier_id', '=', 's.supplier_id')
        ->select(
            'r.r_id',
            'r.po_number',
            'r.invoice_number',
            'r.rr_number',
            'r.received_by',
            'r.receive_date',
            'r.dr_number',
            'r.grand_total',
            'r.remarks',
            's.supplier_name',
            's.address'
        )
        ->where('r.r_id', $r_id)
        ->whereNull('r.deleted_at')
        ->first();

    if (!$rr) {
        return response()->json([
            'success' => false,
            'message' => 'Receiving Report not found.'
        ], 404);
    }

    // Fetch ITEMS (walang duplication)
    $items = DB::table('tbl_receive_items as ri')
        ->leftJoin('tbl_item_code as i', 'ri.ItemCode_id', '=', 'i.ItemCode_id')
        ->select(
            'i.ItemCode',
            'i.product_name',
            'i.description',
            'ri.quantity_order',
            'ri.quantity_received',
            'ri.unit_cost',
            'ri.units',
            'ri.total_cost',
            'ri.status'
        )
        ->where('ri.r_id', $r_id)
        ->whereNull('ri.deleted_at')
        ->get();

    return response()->json([
        'success' => true,
        'message' => "Receiving report ID {$r_id} fetched successfully",
        'data' => [
            'r_id'           => $rr->r_id,
            'po_number'      => $rr->po_number,
            'invoice_number' => $rr->invoice_number,
            'rr_number'      => $rr->rr_number,
            'received_by'    => $rr->received_by,
            'receive_date'   => $rr->receive_date,
            'dr_number'      => $rr->dr_number,
            'supplier_name'  => $rr->supplier_name,
            'address'        => $rr->address,
            'grand_total'    => $rr->grand_total,
            'remarks'        => $rr->remarks,
            'items'          => $items
        ]
    ]);
}

    // -------------------------
    // UPDATE RR
    // -------------------------

public function updateRR(Request $request, $r_id)
{
    $request->validate([
        'supplier_id'          => 'required|integer',
        'po_number'            => 'required|string',
        'dr_number'            => 'required|string',
        'invoice_number'       => 'required|string',
        'remarks'              => 'nullable|string',
        'items'                => 'required|array|min:1',
        'items.*.id'           => 'nullable|integer',
        'items.*.ItemCode_id'  => 'required|integer',
        'items.*.quantity_order'    => 'required|numeric',
        'items.*.quantity_received'  => 'required|numeric',
        'items.*.unit_cost'         => 'required|numeric',
        'items.*.units'             => 'required|string',
    ]);

    DB::beginTransaction();

    try {
        $rr = ModelReceived::with('receivedItems')->findOrFail($r_id);

        // 🔹 Save old data for audit
        $oldData = [
            'rr'    => $rr->toArray(),
            'items' => $rr->receivedItems()->get()->toArray()
        ];

        // Para malaman kung alin ang natira / na-remove
        $existingItems = ModelReceivedItem::where('r_id', $r_id)->get()->keyBy('id');
        $processedIds  = [];
        $grandTotal    = 0;

        $rrNumber = $rr->rr_number; // very important: ito ang reference

        foreach ($request->items as $row) {
            $itemId       = $row['id'] ?? null;
            $itemCode     = intval($row['ItemCode_id']);
            $qtyOrder     = floatval($row['quantity_order']);
            $qtyReceived  = floatval($row['quantity_received']);
            $unitCost     = floatval($row['unit_cost']);
            $totalCost    = $qtyReceived * $unitCost;
            $status       = ($qtyReceived == $qtyOrder) ? 'Complete' : 'Partial';

            $grandTotal += $totalCost;

            // STEP 1: Hanapin existing item
            $item = null;
            if ($itemId) {
                $item = ModelReceivedItem::find($itemId);
            }
            if (!$item) {
                $item = ModelReceivedItem::where('r_id', $r_id)
                    ->where('ItemCode_id', $itemCode)
                    ->first();
            }

            if ($item) {
                // =======================================
                //  STEP 2: Reverse old ACTIVE transaction
                // =======================================
                ModelTransactions::where('reference', $rrNumber)
                    ->where('ItemCode_id', $item->ItemCode_id)
                    ->where('status', 'ACTIVE')
                    ->update([
                        'status'     => 'REVERSED_CHANGE',
                        'updated_at' => now()
                    ]);

                // =======================================
                //  STEP 3: Adjust stock
                // =======================================
                $oldItemCode = intval($item->ItemCode_id);
                $oldQty      = floatval($item->quantity_received);

                if ($oldItemCode !== $itemCode) {
                    // 💥 Lumipat sa ibang item code

                    // old item - ibawas yung dati
                    $oldStock = ModelStocks::firstOrCreate(['ItemCode_id' => $oldItemCode]);
                    $oldStock->quantity_onhand = $oldStock->quantity_onhand - $oldQty;
                    $oldStock->save();

                    // new item - idagdag yung bago
                    $newStock = ModelStocks::firstOrCreate(['ItemCode_id' => $itemCode]);
                    $newStock->quantity_onhand = $newStock->quantity_onhand + $qtyReceived;
                    $newStock->save();

                } else {
                    // Still same item code – apply diff
                    $diffQty = $qtyReceived - $oldQty;
                    if ($diffQty != 0) {
                        $stock = ModelStocks::firstOrCreate(['ItemCode_id' => $itemCode]);
                        $stock->quantity_onhand = $stock->quantity_onhand + $diffQty;
                        $stock->save();
                    }
                }

                // =======================================
                //  STEP 4: Update item row
                // =======================================
                $item->update([
                    'ItemCode_id'       => $itemCode,
                    'quantity_order'    => $qtyOrder,
                    'quantity_received' => $qtyReceived,
                    'unit_cost'         => $unitCost,
                    'total_cost'        => $totalCost,
                    'status'            => $status,
                    'units'             => $row['units'] ?? '',
                ]);

            } else {
                // =======================================
                //  NEW item row
                // =======================================
                $item = ModelReceivedItem::create([
                    'r_id'              => $r_id,
                    'ItemCode_id'       => $itemCode,
                    'quantity_order'    => $qtyOrder,
                    'quantity_received' => $qtyReceived,
                    'unit_cost'         => $unitCost,
                    'total_cost'        => $totalCost,
                    'status'            => $status,
                    'units'             => $row['units'] ?? '',
                ]);

                // Add stock
                $stock = ModelStocks::firstOrCreate(['ItemCode_id' => $itemCode]);
                $stock->quantity_onhand = $stock->quantity_onhand + $qtyReceived;
                $stock->save();
            }

            // =======================================
            //  STEP 5: Insert new ACTIVE transaction
            // =======================================
            ModelTransactions::create([
                'ItemCode_id'      => $itemCode,
                'movement_type'    => 'IN',
                'transaction_type' => 'Received',   // must match ENUM exactly
                'quantity'         => $qtyReceived,
                'reference'        => $rrNumber,    // NOT $r_id
                'reference_type'   => 'RR #',
                'status'           => 'ACTIVE',
                'user_id'          => auth()->id() ?? null,
                'created_by'       => auth()->user()?->fullname ?? 'System',
            ]);

            $processedIds[] = $item->id;
        }

        // =======================================
        //  STEP 6: Handle removed items
        // =======================================
        foreach ($existingItems as $existingItem) {
            if (!in_array($existingItem->id, $processedIds)) {

                // Bawas stock ng na-remove na item
                $stock = ModelStocks::firstOrCreate(['ItemCode_id' => $existingItem->ItemCode_id]);
                $stock->quantity_onhand = $stock->quantity_onhand - $existingItem->quantity_received;
                $stock->save();

                // Mark old ACTIVE transactions as REVERSED_DELETE
                ModelTransactions::where('reference', $rrNumber)
                    ->where('ItemCode_id', $existingItem->ItemCode_id)
                    ->where('status', 'ACTIVE')
                    ->update([
                        'status'     => 'REVERSED_DELETE',
                        'updated_at' => now()
                    ]);

                // Soft delete item row
                $existingItem->delete();
            }
        }

        // =======================================
        //  STEP 7: Update RR totals and header status
        // =======================================
        $allComplete = ModelReceivedItem::where('r_id', $r_id)
            ->where('status', '!=', 'Complete')
            ->whereNull('deleted_at')
            ->count() === 0;

        $rr->update([
            'grand_total' => $grandTotal,
            'remarks'     => $allComplete ? 'Complete' : 'Partial',
            'status'      => $allComplete ? 'Complete' : 'Partial', // kung meron kang status field sa tbl_received
        ]);

        // =======================================
        //  STEP 8: Log history
        // =======================================
        $newData = [
            'rr'    => $rr->fresh()->toArray(),
            'items' => $rr->receivedItems()->get()->toArray()
        ];

        RRHistory::create([
            'r_id'     => $rr->r_id,
            'user_id'  => auth()->id() ?? 0,
            'old_data' => $oldData,
            'new_data' => $newData,
            'action'   => 'update',
        ]);

        DB::commit();

        return response()->json([
            'success'     => true,
            'message'     => 'RR updated successfully',
            'grand_total' => $grandTotal,
            'status'      => $allComplete ? 'Complete' : 'Partial'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('updateRR ERROR: ' . $e->getMessage() . ' -- ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Failed to update RR',
            'error'   => $e->getMessage()
        ], 500);
    }
}




    // -------------------------
    // SOFT DELETE RR
    // -------------------------
    
public function softDeleteRR($r_id)
{
    DB::beginTransaction();

    try {
        // 1️⃣ Get RR
        $rr = ModelReceived::with('receivedItems')->find($r_id);
        if (!$rr) {
            return response()->json([
                'success' => false,
                'message' => "Receiving Report ID {$r_id} not found."
            ], 404);
        }

        // 2️⃣ Save old data for audit
        $oldData = [
            'rr' => $rr->toArray(),
            'items' => $rr->receivedItems->toArray()
        ];

        // 3️⃣ Reverse stock + mark transactions REVERSED_DELETE
        foreach ($rr->receivedItems as $item) {

            // 🔥 Correct stock reversal — KEEP NEGATIVE SUPPORT
            $stock = ModelStocks::firstOrCreate(['ItemCode_id' => $item->ItemCode_id]);
            $stock->quantity_onhand = $stock->quantity_onhand - $item->quantity_received;
            $stock->save();

            // 🔥 Correct transaction reversal — use rr_number NOT r_id
            ModelTransactions::where('reference', $rr->rr_number)
                ->where('ItemCode_id', $item->ItemCode_id)
                ->where('status', 'ACTIVE')
                ->update([
                    'status' => 'REVERSED_DELETE',
                    'updated_at' => now()
                ]);
        }

        // 4️⃣ Soft delete parent RR
        $rr->delete();

        // 5️⃣ Soft delete child items
        ModelReceivedItem::where('r_id', $r_id)->delete();

        // 6️⃣ Log history
        RRHistory::create([
            'r_id' => $r_id,
            'user_id' => auth()->id() ?? 0,
            'old_data' => $oldData,
            'new_data' => [
                'rr' => ModelReceived::withTrashed()->find($r_id),
                'items' => ModelReceivedItem::withTrashed()->where('r_id', $r_id)->get()
            ],
            'action' => 'soft_delete',
        ]);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Receiving Report ID {$r_id} and all related items were soft deleted successfully."
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('softDeleteRR ERROR: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to soft delete Receiving Report.',
            'error' => $e->getMessage()
        ], 500);
    }
}



    // -------------------------
    // GET SOFT DELETED RR
    // -------------------------
 public function getSoftDeletedRR(Request $request)
{
    $perPage = $request->input('per_page', 10);
    $search = $request->input('search');

    $query = DB::table('tbl_receive as r')
        ->join('tbl_suppliers as s', 'r.supplier_id', '=', 's.supplier_id')
        ->join('tbl_receive_items as ri', 'r.r_id', '=', 'ri.r_id')
        ->join('tbl_item_code as i', 'ri.ItemCode_id', '=', 'i.ItemCode_id')
        ->select(
            'r.r_id',
            'r.rr_number',
            'r.po_number',
            'r.dr_number',
            'r.invoice_number',
            's.supplier_name',
            'r.receive_date',
            'r.remarks',
            'ri.quantity_order',
            'ri.quantity_received',
            'ri.unit_cost',
            'ri.total_cost',
            'ri.status',
            'i.product_name',
            'i.description',
            'i.ItemCode as accounting_code'
        )
        ->whereNotNull('r.deleted_at') // soft-deleted only
        ->when($search, function ($q) use ($search) {
            $q->where(function ($subQ) use ($search) {
                $subQ->where('r.rr_number', 'like', "%{$search}%")
                     ->orWhere('r.invoice_number', 'like', "%{$search}%")
                     ->orWhere('r.po_number', 'like', "%{$search}%")
                     ->orWhere('s.supplier_name', 'like', "%{$search}%")
                     ->orWhere('i.product_name', 'like', "%{$search}%");
            });
        })
        ->orderBy('r.deleted_at', 'desc')
        ->get();

    // Group by RR number and include items
    $grouped = $query->groupBy('rr_number')->map(function ($items, $rr_number) {
        $first = $items->first();
        return [
            'r_id' => $first->r_id,
            'rr_number' => $rr_number,
            'po_number' => $first->po_number,
            'dr_number' => $first->dr_number,
            'invoice_number' => $first->invoice_number,
            'supplier_name' => $first->supplier_name,
            'receive_date' => $first->receive_date,
            'remarks' => $first->remarks,
            'items' => $items->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'description' => $item->description,
                    'accounting_code' => $item->accounting_code,
                    'quantity_order' => $item->quantity_order,
                    'quantity_received' => $item->quantity_received,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'status' => $item->status,
                ];
            })->values(),
        ];
    })->values();

    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $currentItems = $grouped->slice(($currentPage - 1) * $perPage, $perPage)->values();
    $paginated = new LengthAwarePaginator(
        $currentItems,
        $grouped->count(),
        $perPage,
        $currentPage,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    return response()->json([
        'success' => true,
        'message' => 'Soft deleted Receiving Reports fetched successfully.',
        'data' => $paginated->items(),
        'meta' => [
            'current_page' => $paginated->currentPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
            'last_page' => $paginated->lastPage(),
            'from' => $paginated->firstItem(),
            'to' => $paginated->lastItem(),
        ],
    ]);
}
public function check_item($po_number, $ItemCode_id)
{
    try {
        // Get the last receiving item for this PO and item
        $item = DB::table('tbl_receive_items as ri')
            ->join('tbl_receive as r', 'r.r_id', '=', 'ri.r_id')
            ->where('r.po_number', $po_number)
            ->where('ri.ItemCode_id', $ItemCode_id)
            ->select('ri.status')
            ->orderBy('ri.id', 'desc') // latest receive
            ->first();

        if ($item) {
            return response()->json([
                'exists' => true,
                'status' => $item->status, // Partial or Complete
            ]);
        } else {
            return response()->json([
                'exists' => false,
                'status' => null,
            ]);
        }
    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}


public function checkPOstatus($po_number)
{
    try {
        $po = DB::table('tbl_receive as r')
            ->where('r.po_number', $po_number)
            ->orderBy('r.r_id', 'desc')
            ->first();

        if (!$po) {
            return response()->json(['status' => 'not_found']);
        }

        return response()->json([
            'status' => $po->remarks  // Pending / Partial / Complete
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}

public function get_item_order($po_number, $ItemCode_id)
{
    try {
        // Hanapin yung existing order sa tbl_receive_items para sa PO at item
        $orderQty = DB::table('tbl_receive_items as ri')
            ->join('tbl_receive as r', 'r.r_id', '=', 'ri.r_id')
            ->where('r.po_number', $po_number)
            ->where('ri.ItemCode_id', $ItemCode_id)
            ->value('quantity_order');

        return response()->json([
            'quantity_order' => $orderQty ?? 0
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => true,
            'message' => $e->getMessage()
        ], 500);
    }
}

 public function checkPOComplete($po_number)
{
    try {
        // Get original order per item (from the first RR)
        $items = DB::table('tbl_receive_items as ri')
            ->join('tbl_receive as r', 'r.r_id', '=', 'ri.r_id')
            ->where('r.po_number', $po_number)
            ->select(
                'ri.ItemCode_id',
                DB::raw('MAX(ri.quantity_order) as original_order'),
                DB::raw('SUM(ri.quantity_received) as total_received')
            )
            ->groupBy('ri.ItemCode_id')
            ->whereNull('ri.deleted_at')
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No receiving records found for this PO.'
            ], 404);
        }

        // Check if ALL items are fully received
        $isComplete = $items->every(function ($item) {
            return $item->original_order > 0 && $item->original_order == $item->total_received;
        });

        return response()->json([
            'success' => true,
            'po_complete' => $isComplete
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}


}
