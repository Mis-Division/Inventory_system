<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelMcrt extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_mcrt';
    protected $primaryKey = 'mcrt_id';

    protected $fillable = [
        'mcrt_number',
        'returned_by',
        'work_order',
        'job_order',
        'received_by',
        'grand_total',
    ];

    public function items()
    {
        return $this->hasMany(ModelMcrtItem::class, 'mcrt_id', 'mcrt_id');
    }
protected static function boot(){
        parent::boot();

        static::creating (function ($model){
            $latest = self::orderBy('mcrt_number', 'desc')->first();

            $nextNumber = 1;
            if($latest && $latest->mcrt_number){
                $latestNum = (int) preg_replace('/[^0-9]/', '',$latest->mcrt_number);
                $nextNumber = $latestNum + 1;
            }

            $model->mcrt_number = 'MCRT No.' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }




}
