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
   public function CreateMrv(Request $request)
{
    // VALIDATION
    $validate = $request->validate([
        'requested_by' => 'required|string|max:255',
        'department'   => 'required|string|max:255',
        'approved_by'  => 'required|string|max:255',
        'created_by'   => 'required|string|max:255',

        'items' => 'required|array|min:1',
        'items.*.itemcode_id'   => 'required|exists:tbl_item_code,ItemCode_id',
        'items.*.requested_qty' => 'required|integer|min:1',
        'items.*.product_type'  => 'required|string|max:255',
    ]);

    DB::beginTransaction();

    try {

        // CREATE MRV HEADER
        $createMrv = ModelMrv::create([
            'requested_by' => $request->requested_by,
            'department'   => $request->department,
            'approved_by'  => $request->approved_by,
            'created_by'   => $request->created_by,
        ]);

        // LOOP ITEMS
        foreach ($request->items as $item) {

            // GET STOCK (LOCK FOR UPDATE)
            // $stock = ModelStocks::where('ItemCode_id', $item['itemcode_id'])
            //                     ->lockForUpdate()
            //                     ->first();


            $stock = DB::table('tbl_stocks as s')
                    ->join('tbl_item_code as i', 'i.ItemCode_id', '=', 's.ItemCode_id')
                    ->select(
                        's.ItemCode_id',
                        'i.product_name',
                        's.quantity_onhand'
                    )
                    ->where('s.ItemCode_id', $item['itemcode_id'])
                    ->lockForUpdate()
                    ->first();

            if (!$stock) {
                throw new \Exception("Item Not Found!");
            }

            // TYPECAST VALUES FOR SAFE COMPARISON
            $onhand   = (int)$stock->quantity_onhand;
            $requested = (int)$item['requested_qty'];

            // CHECK STOCK SUFFICIENCY
            if ($onhand < $requested) {
                throw new \Exception("Insufficient Stock Items: {$stock->product_name}");
            }

            // DEDUCT STOCK
            // $stock->quantity_onhand = $onhand - $requested;
            // $stock->save();

            DB::table('tbl_stocks')
                ->where('ItemCode_id', $item['itemcode_id'])
                ->update([
                    'quantity_onhand' => $onhand - $requested
                ]);

            // CREATE MRV ITEM
            ModelMrvItems::create([
                'mrv_id'        => $createMrv->mrv_id,
                'itemcode_id'   => $item['itemcode_id'],
                'requested_qty' => $item['requested_qty'],
                'product_type'  => $item['product_type'],
            ]);

            // CREATE TRANSACTION
            ModelTransactions::create([
                'ItemCode_id'    => $item['itemcode_id'],
                'movement_type'  => 'OUT',
                'quantity'       => $requested,
                'reference'      => $createMrv->mrv_id,     
                'reference_type' => 'MRV ID',
                'user_id'        => auth()->id() ?? null,
                'created_by'     => auth()->user()->fullname ?? 'system',
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'MRV Successfully Created with Deduction of Stocks!',
            'mrv_id'  => $createMrv->mrv_id
        ], 201);

    } catch (\Exception $e) {

        DB::rollback();

        return response()->json([
            'success' => false,
            'message' => 'Failed to create MRV!',
            'details' => $e->getMessage(),
        ], 500);
    }
}

//Display lahat ng request sa MRV        
public function displayMrv(Request $request)
{
    $queryStr = $request->input('query');
    $perPage  = $request->input('per_page', 10);

    // Build the query
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

    // Use Laravel paginate to handle paging in the DB
    $paginated = $query->paginate($perPage);

    // Prepare the response
    if ($paginated->isEmpty()) {
        $message = $queryStr
            ? "No MRV matching '{$queryStr}' found!"
            : "No MRV found.";
    } else {
        $message = $queryStr
            ? "MRV matching '{$queryStr}' fetched successfully!"
            : "All MRV fetched successfully.";
    }

    return response()->json([
        'success' => true,
        'message' => $message,
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


    public function gerMrvDetails($mrv_id){
        $mrv = DB::table('tbl_mrv')
            ->select(
                'mrv_id',
                'mrv_number',
                'requested_by',
                'approved_by',
                'created_by',
                'created_at',
                'status',
            )
            ->where('mrv_id', $mrv_id)
            ->whereNull('deleted_at')
            ->first();

            if(!$mrv){
                return response()->json([
                    'success' => failed,
                    'message' =>'MRV not Found!'
                ], 404);
            }

            $items = DB::table('tbl_mrv_items as m')
                ->join('tbl_item_code as i', 'i.ItemCode_id',  '=', 'm.itemcode_id')
                ->select(
                    'i.ItemCode',
                    'm.requested_qty',
                    'm.product_type'
                )
                ->where('m.mrv_id', $mrv_id)
                ->get();

            return response()->json([
                'success' => true,
                'message' => "Mrv Id {$mrv_id} fetch successfully",
                'data' => [
                    'mrv_id'        =>$mrv->mrv_id,
                    'mrv_number'   =>$mrv->mrv_number,
                    'requested_by' =>$mrv->requested_by,
                    'approved_by'  =>$mrv->approved_by,
                    'created_by'   =>$mrv->created_by,
                    'created_at'   =>$mrv->created_at,
                    'status'       =>$mrv->status,
                    'items'         =>$items
                ]
            ]);


    }

}
