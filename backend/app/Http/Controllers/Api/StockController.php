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
    // Validate item exists
    $itemExists = DB::table('tbl_item_code')
        ->where('ItemCode_id', $itemCodeId)
        ->exists();

    if (!$itemExists) {
        return response()->json([
            'success' => false,
            'message' => 'No data found.'
        ], 404);
    }

    $ledger = DB::table('tbl_item_code as i')

        // TRANSACTIONS
        ->leftJoin('tbl_transactions as t', function ($join) {
            $join->on('i.ItemCode_id', '=', 't.ItemCode_id')
                 ->where('t.status', 'ACTIVE');
        })

        // STOCKS
        ->leftJoin('tbl_stocks as s', 's.ItemCode_id', '=', 'i.ItemCode_id')

        // MCRT ITEMS → JOIN USING REGEXP TO EXTRACT NUMBER FROM REFERENCE
        ->leftJoin('tbl_mcrt_items as mci', function ($join) {
            $join->on('mci.itemcode_id', '=', 't.ItemCode_id')
                 ->on('mci.mcrt_id', '=', DB::raw("
                    CAST(REGEXP_REPLACE(t.reference, '[^0-9]', '') AS UNSIGNED)
                 "));
        })

        ->selectRaw("
            i.ItemCode_id,
            i.ItemCode,
            i.product_name,

            t.transaction_id,
            t.created_at,
            t.movement_type,
            t.transaction_type,
            t.reference,
            t.remarks,

            /* FINAL REMARKS LOGIC */
            CASE
                WHEN t.transaction_type = 'RETURN'
                    THEN COALESCE(mci.condition, '-')
                WHEN t.transaction_type = 'REQUEST'
                    THEN COALESCE(t.remarks, '-')
                ELSE '-'
            END AS remarks,

            /* DEBIT / CREDIT */
            CASE WHEN t.movement_type = 'IN'  THEN t.quantity ELSE 0 END AS Debit,
            CASE WHEN t.movement_type = 'OUT' THEN t.quantity ELSE 0 END AS Credit,

            /* RUNNING BALANCE */
            COALESCE(
                SUM(
                    CASE
                        WHEN t.movement_type = 'IN'  THEN t.quantity
                        WHEN t.movement_type = 'OUT' THEN -t.quantity
                        ELSE 0
                    END
                ) OVER (
                    PARTITION BY t.ItemCode_id
                    ORDER BY t.created_at, t.transaction_id
                    ROWS UNBOUNDED PRECEDING
                ),
            0) AS Running_Balance,

            /* STOCKS */
            COALESCE(s.quantity_onhand, 0) AS main_stock,
            COALESCE(s.usable_stock, 0) AS usable_stock,
            (COALESCE(s.quantity_onhand,0) + COALESCE(s.usable_stock,0)) AS Current_Stock
        ")

        ->where('i.ItemCode_id', $itemCodeId)
        ->orderBy('t.created_at', 'asc')
        ->orderBy('t.transaction_id', 'asc')
        ->get();

    return response()->json([
        'success' => true,
        'data' => $ledger,
    ]);
}









public function ledgerGroupedPaginated(Request $request)
{
    $perPage     = $request->input('perPage', 1000000);
    $search      = $request->input('search', '');
    $productType = $request->input('product_type', null);

    $query = DB::table('tbl_item_code as i')
        ->leftJoin('tbl_stocks as s', 's.ItemCode_id', '=', 'i.ItemCode_id')

        // Filters
        ->when($productType, fn($q) => $q->where('i.item_category', $productType))
        ->when($search, fn($q) => $q->where('i.product_name', 'like', "%{$search}%"))

        ->selectRaw("
            i.ItemCode_id,
            i.ItemCode,
            i.product_name,
            i.description,
            i.item_category AS product_type,
            i.accounting_code,

            COALESCE(s.quantity_onhand, 0) AS Current_Stock,
            COALESCE(s.usable_stock, 0) AS Usable_Stock,
            (COALESCE(s.quantity_onhand, 0) + COALESCE(s.usable_stock, 0)) AS Total_Stock
        ")

        ->orderBy('i.product_name');

    $paginated = $query->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => [
            'stocks' => $paginated->items(),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'last_page'    => $paginated->lastPage(),
            ]
        ]
    ]);
}







}
