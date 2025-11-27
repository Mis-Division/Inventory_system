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
    ];

    public $timestamps = true;

    public function stock()
    {
        return $this->hasOne(ModelStocks::class, 'ItemCode_id');
    }

    public function receivedItems()
    {
        return $this->hasMany(ModelReceivedItem::class, 'ItemCode_id');
    }
}
