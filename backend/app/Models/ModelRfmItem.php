<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelRfmItem extends Model
{
    protected $table = 'tbl_rfm_items';
     use SoftDeletes;

    protected $fillable = [
        'rfm_id',
        'itemcode_id',
        'material_description',
        'requested_qty',
        'status',
        'remarks',
    ];

    public function rfm()
    {
        return $this->belongsTo(ModelRfm::class, 'rfm_id');
    }

    public function item()
    {
        return $this->belongsTo(ModelItemCode::class, 'itemcode_id', 'ItemCode_id');
    }
}
