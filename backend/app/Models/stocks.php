<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stocks extends Model
{
    protected $table = 'tbl_stocks';
    protected $primaryKey = 'stock_id'; // ✅ fixed capitalization (was primarykey)
    public $timestamps = true; // ✅ optional but recommended if you have created_at / updated_at

    protected $fillable = [
        'product_id',
        'quantity_onhand',
    ];

    // ✅ Relationship: Each stock belongs to one product
    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id', 'product_id'); // ✅ fixed typo "belongTo" → "belongsTo"
    }
}
