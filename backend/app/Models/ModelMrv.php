<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ModelMrv extends Model
{
    use HasFactory;

    protected $table = 'tbl_mrv';
    protected $primaryKey = 'mrv_id';

    protected $fillable = [
        'mrv_number',
        'requested_by',
        'department',
        'approved_by',
        'created_by',
        'status',
    ];

    public function items(){
        return $this->hasMany(ModelMrvItems::class, 'mrv_id', 'mrv_id');
    }

    protected static function boot(){
        parent::boot();

        static::creating (function ($model){
            $latest = self::orderBy('mrv_id', 'desc')->first();

            $nextNumber = 1;
            if($latest && $latest->mrv_number){
                $latestNum = (int) preg_replace('/[^0-9]/', '',$latest->mrv_number);
                $nextNumber = $latestNum + 1;
            }

            $model->mrv_number = 'MRV No.' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}
