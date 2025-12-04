<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ModelStocks;
use Illuminate\Support\Facades\DB;
use App\Models\ModelTransactions;

class StockController extends Controller
{
   public function ledger(Request $request)
{
    $perPage = $request->input('perPage', 10); // default 10 rows per page
    $page = $request->input('page', 1);

    $query = DB::table('tbl_transactions as t')
        ->join('tbl_item_code as i', 'i.ItemCode_id', '=', 't.ItemCode_id')
        ->where('t.status', 'ACTIVE')
        ->selectRaw("
            i.ItemCode_id,
            i.ItemCode,
            i.product_name,
            i.item_category AS product_type,
            i.accounting_code,
            t.transaction_id,
            t.created_at,
            t.movement_type,
            t.transaction_type,
            t.reference,
            t.reference_type,
            t.user_id,
            t.created_by,

            CASE WHEN t.movement_type = 'IN' THEN t.quantity ELSE 0 END AS Debit,
            CASE WHEN t.movement_type = 'OUT' THEN t.quantity ELSE 0 END AS Credit,

            SUM(
                CASE 
                    WHEN t.movement_type = 'IN' THEN t.quantity
                    WHEN t.movement_type = 'OUT' THEN -t.quantity
                    ELSE 0
                END
            ) OVER (
                PARTITION BY t.ItemCode_id
                ORDER BY t.created_at, t.transaction_id
                ROWS UNBOUNDED PRECEDING
            ) AS Running_Balance
        ");

    // Optional filter: itemCodeId
    $query->when($request->itemCodeId, function ($q) use ($request) {
        $q->where('t.ItemCode_id', $request->itemCodeId);
    });

    // Wrap in subquery + join stocks
    $subQuery = DB::table(DB::raw("({$query->toSql()}) as L"))
        ->mergeBindings($query)
        ->leftJoin('tbl_stocks as s', 's.ItemCode_id', '=', 'L.ItemCode_id')
        ->selectRaw("L.*, s.quantity_onhand AS Current_Stock")
        ->orderBy('L.ItemCode_id')
        ->orderBy('L.created_at')
        ->orderBy('L.transaction_id');

    // Paginate manually
    $total = DB::table(DB::raw("({$subQuery->toSql()}) as final"))
        ->mergeBindings($subQuery)
        ->count();

    $items = DB::table(DB::raw("({$subQuery->toSql()}) as final"))
        ->mergeBindings($subQuery)
        ->skip(($page - 1) * $perPage)
        ->take($perPage)
        ->get();

    return response()->json([
        'success' => true,
        'data' => $items,
        'pagination' => [
            'total' => $total,
            'per_page' => (int)$perPage,
            'current_page' => (int)$page,
            'last_page' => ceil($total / $perPage),
        ]
    ]);
}


public function ledgerById($itemCodeId)
{
    $itemExists = DB::table('tbl_item_code')->where('ItemCode_id', $itemCodeId)->exists();

    if (!$itemExists) {
        return response()->json([
            'success' => false,
            'message' => 'No data found for the given ItemCode_id.'
        ], 404);
    }

    $ledger = DB::table('tbl_item_code as i')
        ->leftJoin('tbl_transactions as t', function($join) {
            $join->on('i.ItemCode_id', '=', 't.ItemCode_id')
                 ->where('t.status', 'ACTIVE');
        })
        ->leftJoin('tbl_stocks as s', 's.ItemCode_id', '=', 'i.ItemCode_id')

        ->leftJoin('tbl_mcrt_items as mci', function($join){
            $join->on('mci.itemcode_id', '=', 't.ItemCode_id')
                 ->where('t.reference_type', 'MCRT');
        })

        ->leftJoin('tbl_mcrt as m', 'm.mcrt_id', '=', 'mci.mcrt_id')

        ->where('i.ItemCode_id', $itemCodeId)

        /** 🔥 KEY FIX (HIDE DELETED MCRT RETURN TRANSACTIONS) */
        ->where(function ($q) {
            $q->where('t.transaction_type', '!=', 'RETURN')
              ->orWhereNull('mci.deleted_at');
        })

        ->selectRaw("
            i.ItemCode_id,
            i.ItemCode,
            i.product_name,
            i.item_category AS product_type,
            i.accounting_code,

            t.transaction_id,
            t.created_at,
            t.movement_type,
            t.transaction_type,
            t.reference,
            t.reference_type,
            t.user_id,
            t.created_by,

            COALESCE(t.status, 'ACTIVE') AS transaction_status,

            COALESCE(
                CASE WHEN t.transaction_type = 'RETURN' THEN mci.`condition` ELSE '' END,
                ''
            ) AS mcrt_condition,

            CASE WHEN t.movement_type = 'IN' THEN t.quantity ELSE 0 END AS Debit,
            CASE WHEN t.movement_type = 'OUT' THEN t.quantity ELSE 0 END AS Credit,

            COALESCE(
                SUM(
                    CASE 
                        WHEN t.movement_type = 'IN' THEN t.quantity
                        WHEN t.movement_type = 'OUT' THEN -t.quantity
                        ELSE 0
                    END
                ) OVER (
                    PARTITION BY t.ItemCode_id
                    ORDER BY t.created_at, t.transaction_id
                    ROWS UNBOUNDED PRECEDING
                ),
                0
            ) AS Running_Balance,

            COALESCE(s.quantity_onhand, 0) AS Current_Stock
        ")

        ->orderBy('t.created_at')
        ->orderBy('t.transaction_id')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $ledger,
    ]);
}






public function ledgerGroupedPaginated(Request $request)
{
    $perPage = $request->input('perPage', 10);
    $search  = $request->input('search', '');
    $productType = $request->input('product_type', null);

    $query = DB::table('tbl_item_code as i')
        ->leftJoin('tbl_stocks as s', 's.ItemCode_id', '=', 'i.ItemCode_id')
        ->when($productType, fn($q) => $q->where('i.item_category', $productType))
        ->when($search, fn($q) => $q->where('i.product_name', 'like', "%{$search}%"))
        ->select(
            'i.ItemCode_id',
            'i.ItemCode',
            'i.product_name',
            'i.description',
            'i.item_category as product_type',
            'i.accounting_code',
            DB::raw('COALESCE(s.quantity_onhand,0) as Current_Stock')
        )
        ->groupBy(
            'i.ItemCode_id',
            'i.ItemCode',
            'i.product_name',
             'i.description',
            'i.item_category',
            'i.accounting_code',
            's.quantity_onhand'
        )
        ->orderBy('i.product_name');

    $paginated = $query->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => [
            'stocks' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
            ]
        ]
    ]);
}







}
