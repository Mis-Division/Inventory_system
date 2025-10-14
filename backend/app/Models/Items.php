<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
     protected $table = 'tbl_item_code';
    protected $primaryKey = 'ItemCode_id';
    protected $fillable = ['ItemCode', 'description'];
    public $timestamps = true; 
}
