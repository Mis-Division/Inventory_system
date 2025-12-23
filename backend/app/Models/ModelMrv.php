<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelMrv extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_mrv';
    protected $primaryKey = 'mrv_id';

    protected $fillable = [
        'mrv_number',
        'mrv_date',
        'rfm_id',
        'rfm_number',
        'requested_by',
        'department',
        'approved_by',
        'approved_by_Gm',
        'incharge',
        'date_release',
        'created_by',
        'status',
        'remarks',
        'deleted_by',
    ];

    protected $casts = [
        'mrv_date' => 'date',
    ];

    // ==========================
    // RELATIONSHIPS
    // ==========================
    public function items()
    {
        return $this->hasMany(ModelMrvItems::class, 'mrv_id', 'mrv_id');
    }

    // ==========================
    // AUTO MRV NUMBER GENERATOR
    // ==========================
    protected static function booted()
    {
        static::creating(function ($model) {

            // Generate only if empty
            if (empty($model->mrv_number)) {

                $lastId = static::withTrashed()->max('mrv_id') ?? 0;
                $nextId = $lastId + 1;

                $model->mrv_number = 'MRV-' . str_pad(
                    $nextId,
                    10,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
