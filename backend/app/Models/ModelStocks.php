<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelStocks extends Model
{
    use HasFactory;

    protected $table = 'tbl_stocks';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'ItemCode_id',
        'quantity_onhand',
    ];

    /**
     * Relationships
     */

    // Each stock record belongs to one item
    public function item()
    {
        return $this->belongsTo(Items::class, 'ItemCode_id');
    }
}
