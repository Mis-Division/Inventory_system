<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Items;
use App\Models\ModelStocks;
use App\Models\ModelTransactions;
use App\Models\Suppliers;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;


class ItemImportController extends Controller
{
 public function import(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv'
    ]);

    $filePath = $request->file('file')->getRealPath();

    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    DB::beginTransaction();

    try {

        foreach ($rows as $index => $row) {

            if ($index == 0) continue; // Skip header

            // Clean inputs
            $productName     = trim(strtolower($row[0] ?? ''));
            $description     = trim($row[1] ?? '');
            $accountingCode  = trim($row[2] ?? '');
            $category        = trim($row[3] ?? '');
            $units           = trim($row[4] ?? '');
            $quantityOnHand  = trim($row[5] ?? '0');

            if (!$productName) continue;

            // Check if product exists (prevent duplicates)
            $existingItem = Items::where('product_name', $productName)->first();

            if ($existingItem) {

                $stock = ModelStocks::firstOrCreate(
                    ['ItemCode_id' => $existingItem->ItemCode_id],
                    ['quantity_onhand' => 0]
                );

                $stock->update([
                    'quantity_onhand' => $stock->quantity_onhand + (int)$quantityOnHand
                ]);

                continue;
            }

            // Generate UNIQUE ItemCode
            $itemCode = $this->generateUniqueItemCode($productName);

            // Insert Item
            $item = Items::create([
                'ItemCode'        => $itemCode,
                'product_name'    => $productName,
                'description'     => $description,
                'accounting_code' => $accountingCode ?: null,
                'item_category'   => $category ?: null,
                'units'           => $units ?: null,
            ]);

            // Insert Stock
            ModelStocks::create([
                'ItemCode_id'     => $item->ItemCode_id,
                'quantity_onhand' => is_numeric($quantityOnHand) ? (int)$quantityOnHand : 0,
            ]);

            // ⭐ ADD INITIAL BALANCE ENTRY ⭐
            ModelTransactions::create([
                'ItemCode_id'      => $item->ItemCode_id,
                'movement_type'    => 'IN',
                'transaction_type' => 'INITIAL BALANCE',
                'quantity'         => (int)$quantityOnHand,
                'reference'        => 'OPENING STOCK',
                'reference_type'   => 'INITIAL',
                'user_id'          => auth()->id() ?? 1,
                'created_by'       => auth()->user()?->fullname ?? 'system',
                'status'           => 'ACTIVE',
                'updated_by'       => null,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Items imported successfully!'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Import failed: ' . $e->getMessage()
        ], 500);
    }
}



  private function generateUniqueItemCode($product)
{
    // Extract prefix
    $words = preg_split('/\s+|-/', strtolower($product));
    $prefix = '';

    foreach ($words as $w) {
        if (strlen($prefix) >= 3) break;
        $prefix .= strtoupper(substr($w, 0, 1));
    }

    // Extract SIZE (numbers)
    preg_match('/(\d+)/', $product, $matches);
    $size = isset($matches[1]) ? str_pad($matches[1], 3, "0", STR_PAD_LEFT) : "000";

    // Get highest existing numbering
    $last = Items::where('ItemCode', 'LIKE', "{$prefix}-{$size}-%")
        ->orderBy('ItemCode', 'DESC')
        ->first();

    if ($last) {
        $parts = explode('-', $last->ItemCode);
        $nextNum = str_pad(((int)$parts[2]) + 1, 3, '0', STR_PAD_LEFT);
    } else {
        $nextNum = "001";
    }

    return "{$prefix}-{$size}-{$nextNum}";
}



public function importSupplier(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:xlsx,xls,csv'
    ]);

    DB::beginTransaction();

    try {

        $filePath = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        foreach ($rows as $index => $row) {

            if ($index == 0) continue; // Skip header row

            $supplier_no     = trim($row[0] ?? '');
            $account_code    = trim($row[1] ?? '');
            $supplier_name   = trim($row[2] ?? '');
            $address         = trim($row[3] ?? '');
            $contact_no      = trim($row[4] ?? '');
            $contact_person  = trim($row[5] ?? '');
            $tin             = trim($row[6] ?? '');
            $vat_type        = trim($row[7] ?? '');

            // Skip empty rows
            if (!$supplier_name) continue;

            // Avoid duplicate supplier code
            $exists = Suppliers::where('supplier_no', $supplier_no)->first();
            if ($exists) continue;

            Suppliers::create([
                'supplier_no'      => $supplier_no,
                'AccountCode'      => $account_code,
                'supplier_name'    => $supplier_name,
                'address'          => $address ?: null,
                'contact_no'       => $contact_no ?: null,
                'contact_person'   => $contact_person ?: null,
                'tin'              => $tin ?: null,
                'vat_type'         => $vat_type ?: null,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Suppliers imported successfully!'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'success' => false,
            'message' => 'Import failed: ' . $e->getMessage()
        ], 500);
    }
}

}
