<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelMcrtItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_mcrt_items';
    protected $primaryKey = 'mcrt_item_id';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'mcrt_id',
        'itemcode_id',
        'returned_qty',
        'cost',
        'condition',
        'deleted_at',
    ];

    public function mcrt()
    {
        return $this->belongsTo(ModelMcrt::class, 'mcrt_id', 'mcrt_id');
    }
}
