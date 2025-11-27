<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeptHead extends Model
{
    protected $table = 'tbl_depthead';

    protected $fillable = [
        'depthead_name',
        'department',
    ];

    /**
     * Each department head is linked to a user
     * by matching the department field.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'department', 'department');
    }
}
