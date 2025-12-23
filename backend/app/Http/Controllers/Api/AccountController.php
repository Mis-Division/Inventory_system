<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ModelAccountCodes as Account;

class AccountController extends Controller
{
   public function getAccountInfo(Request $request)
{
    try {
        $search = $request->query('search');

        $query = Account::select('account_code', 'description');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('account_code', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $accounts = $query
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Account Codes List',
            'data' => $accounts
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'Failed to fetch account codes',
            'message' => $e->getMessage()
        ], 500);
    }
}

}
