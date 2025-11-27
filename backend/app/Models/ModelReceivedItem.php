<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelReceivedItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_receive_items';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'r_id',
        'ItemCode_id',
        'quantity_order',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'units',
        'status',
    ];

    /**
     * Relationships
     */

    // Each received item belongs to one receive transaction
    public function received()
    {
        return $this->belongsTo(ModelReceived::class, 'r_id', 'r_id');
    }

    // Each received item belongs to one product/item code
    public function item()
    {
        return $this->belongsTo(Items::class, 'ItemCode_id');
    }
}
