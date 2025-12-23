<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelMrvItems extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'tbl_mrv_items';
    protected $primarykey = 'mrv_item_id';
    

    protected $fillable = [
        'mrv_id',
        'itemcode_id',
        'requested_qty',
        'product_type',
        'issued_qty',
        'status'
    ];

    public function mrv(){
        return $this->belongsTo(ModelMrv::class, 'mrv_id', 'mrv_id');
    }
    public function itemcode(){
        return $this->belongsTo(Items::class, 'itemcode_id', 'ItemCode_id');
    }
}
