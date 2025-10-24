<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stocks extends Model
{
    protected $table = 'tbl_stocks';
    public $timestamps = true;

    protected $fillable = [
        'item_code',
        'product_name',
        'descriptions',
        'quantity_onhand',
        'quantity_in_stock',
        'unit_cost',
        'product_type',

    ];


}
