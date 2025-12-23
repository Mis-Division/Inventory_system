<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelMctItems extends Model
{
     use HasFactory, SoftDeletes;

    protected $table = 'tbl_mct_items';
    protected $primaryKey = 'mct_item_id';

    protected $fillable = [
        'mct_id',
        'account_code',
        'itemcode_id',
        'requested_qty',
        'unit_cost',
        'total_amount',
        'remarks',
        'status',
    ];

    /**
     * Relationship: Item belongs to MCT header
     */
    public function mct()
    {
        return $this->belongsTo(ModelMct::class, 'mct_id', 'mct_id');
    }
}
