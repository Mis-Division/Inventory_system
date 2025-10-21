<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccess extends Model
{
    protected $table = 'tbl_user_access';

    protected $fillable = [
        'user_id',
        'module_name',
        'parent_module',
        'can_add',
        'can_edit',
        'can_view',
        'can_delete',
    ];

    protected $casts = [
        'can_add' => 'boolean',
        'can_edit' => 'boolean',
        'can_view' => 'boolean',
        'can_delete' => 'boolean',
    ];

    /**
     * Access belongs to a user
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
