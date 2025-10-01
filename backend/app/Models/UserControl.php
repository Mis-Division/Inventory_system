<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserControl extends Model
{
    use HasFactory;

    protected $table = 'tbl_user_controls'; // matches your migration

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'role',
        'status',
    ];

    // Relation back to user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
