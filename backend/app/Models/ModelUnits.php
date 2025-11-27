<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelUnits extends Model
{
     public $timestamps = true;
     protected $table= 'tbl_units';
     protected $primaryKey = 'id';

     protected $fillable = [
        'unit_name',
     ];
}
