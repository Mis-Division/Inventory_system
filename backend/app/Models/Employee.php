<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    // Primary key (optional, only if not "id")
    protected $primaryKey = 'id';

    protected $table = 'tbl_employee';

    // Mass-assignable fields
    protected $fillable = [
        'employee_id',
        'employee_name',
        'employee_department',
    ];
}
