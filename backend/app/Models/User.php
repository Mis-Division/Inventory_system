<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'fullname',
        'email',
        'username',
        'password',
        'department',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Automatically hash password when setting
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /**
     * Relation: user has one control
     */
    public function access()
    {
        return $this->hasMany(UserAccess::class);
    }

    public function control()
    {
        return $this->hasOne(UserControl::class);
    }

    public function modules()
    {
        return $this->hasMany(UserAccess::class, 'user_id', 'id');
    }
}
