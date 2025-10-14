<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = 'tbl_products';
    protected $primaryKey = 'product_id';
    public $timestamps = true; // ✅ add this if your table has created_at / updated_at columns

    protected $fillable = [
        'item_code',
        'product_name',
        'description',
        'quantity_inStock',
        'unitPrice',
        'product_type',
    ];

    // ✅ Relationship: One product has many stocks
    public function stocks()
    {
        return $this->hasMany(Stocks::class, 'product_id', 'product_id');
    }
}
