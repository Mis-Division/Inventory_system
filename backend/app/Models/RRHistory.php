<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ModelReceived;

class RRHistory extends Model
{
    protected $table = 'tbl_rr_history';
    protected $fillable = ['r_id', 'user_id', 'old_data', 'new_data', 'action'];
    protected $casts = ['old_data' => 'array', 'new_data' => 'array'];

    public function receive()
    {
        return $this->belongsTo(ModelReceived::class, 'r_id', 'r_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id', 'id');
    }
}
