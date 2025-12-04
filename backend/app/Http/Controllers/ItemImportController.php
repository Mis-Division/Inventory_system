<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Items;
use App\Models\ModelStocks;
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

        foreach ($rows as $index => $row) {

            if ($index == 0) continue; // Skip header row

            $productName    = trim($row[0] ?? '');
            $description    = trim($row[1] ?? '');
            $accountingCode = trim($row[2] ?? '');
            $category       = trim($row[3] ?? '');
            $units          = trim($row[4] ?? '');

            if (!$productName) continue;

            // 1️⃣ Generate ItemCode
            $itemCode = $this->generateItemCode($productName);

            // 2️⃣ Create Item
            $item = Items::create([
                'ItemCode'        => $itemCode,
                'product_name'    => $productName,
                'description'     => $description,
                'accounting_code' => $accountingCode ?: null,
                'item_category'   => $category ?: null,
                'units'           => $units ?: null,
            ]);

            // 3️⃣ Create stock row (default 0)
            ModelStocks::create([
                'ItemCode_id'     => $item->ItemCode_id,
                'quantity_onhand' => 0,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Items imported successfully with auto ItemCodes!'
        ]);
    }

    private function generateItemCode($product)
{
    // 🔹 Extract first letters of each word (max 3 letters)
    $words = preg_split('/\s+|-/', $product);
    $prefix = '';

    foreach ($words as $w) {
        if (strlen($prefix) >= 3) break;
        $prefix .= strtoupper(substr($w, 0, 1));
    }

    // 🔹 Detect numbers (size) or fallback to 000
    preg_match('/(\d+)/', $product, $matches);
    $size = isset($matches[1]) ? str_pad($matches[1], 3, "0", STR_PAD_LEFT) : "000";

    // 🔹 Ensure no double dash
    $prefix = trim($prefix, "-");

    // 🔹 Count existing codes with same prefix & size
    $count = \App\Models\Items::where('ItemCode', 'LIKE', "{$prefix}-{$size}-%")->count();

    $next = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

    return "{$prefix}-{$size}-{$next}";
}

}
