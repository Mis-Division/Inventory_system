<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    protected $table = 'tbl_user_access';

    protected $fillable = [
        'user_id',
        'module_name',   // match the JSON key
        'parent_module',
        'can_view',
        'can_add',
        'can_edit',
        'can_delete',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
