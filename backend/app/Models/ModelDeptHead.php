<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelDeptHead extends Model
{
    protected $table = "tbl_depthead";

    protected $fillable = [
        'depthead_name',
        'department'
    ];
}
