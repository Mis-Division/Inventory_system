<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    use HasFactory;

    protected $table = 'tbl_item_code';
    protected $primaryKey = 'ItemCode_id';

    protected $fillable = [
        'ItemCode',
        'product_name',
        'description',
        'accounting_code',
        'item_category',
        'units',
    ];

    public $timestamps = true;

    // ============================
    // AUTO GENERATE ITEMCODE HERE
    // ============================
    public static function boot()
    {
        parent::boot();

        static::creating(function ($item) {

            if (!$item->ItemCode) {
                $item->ItemCode = self::generateItemCode($item->product_name);
            }
        });
    }

    // ============= ITEMCODE GENERATOR =============
    public static function generateItemCode($productName)
    {
        // 1. PREFIX (first 3 letters of first 3 words)
        $words = explode(" ", preg_replace('/[^A-Za-z0-9 ]/', '', $productName));

        $prefix = strtoupper(
            substr($words[0] ?? '', 0, 1) .
            substr($words[1] ?? '', 0, 1) .
            substr($words[2] ?? '', 0, 1)
        );

        // 2. SIZE extraction: find 100mm2, 3.5mm2, 0.75mm2, etc.
        preg_match('/(\d+(\.\d+)?)\s*mm2/i', $productName, $matches);

        if ($matches) {
            $rawSize = $matches[1]; 
            $size = str_replace('.', '', $rawSize); 
        } else {
            $size = "000"; 
        }

        // Always min 3 digits
        $size = str_pad($size, 3, '0', STR_PAD_LEFT);

        // 3. Find latest counter for prefix+size
        $like = "{$prefix}-{$size}-%";

        $latest = self::where('ItemCode', 'LIKE', $like)
            ->orderBy('ItemCode', 'desc')
            ->first();

        if ($latest) {
            $lastCounter = (int) substr($latest->ItemCode, -3);
            $counter = $lastCounter + 1;
        } else {
            $counter = 1;
        }

        $counter = str_pad($counter, 3, '0', STR_PAD_LEFT);

        // 4. Final format
        return "{$prefix}-{$size}-{$counter}";
    }

    // ============================
    // RELATIONSHIPS
    // ============================

    public function stock()
    {
        return $this->hasOne(ModelStocks::class, 'ItemCode_id');
    }

    public function receivedItems()
    {
        return $this->hasMany(ModelReceivedItem::class, 'ItemCode_id');
    }
}
