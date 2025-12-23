<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelRfm extends Model
{
    use SoftDeletes;

    protected $table = 'tbl_rfm';
    protected $primaryKey = 'rfm_id';
    public $timestamps = true;

    protected $fillable = [
        'rfm_number',
        'rfm_date',
        'work_description',
        'location',
        'work_type',
        'primary_lines_retired',
        'secondary_lines_retired',
        'busted_transformer',
        'service_drop_wire',
        'cut_of_assembly',
        'meters',
        'poles',
        'mco_details',
        'remarks',
        'requested_by',
        'department',
        'area_engineering',
        'warehouse_initial',
        'warehouse_date',
        'created_by',
        'delete_remarks',
        'deleted_by',
    ];

    public function items()
    {
        return $this->hasMany(ModelRfmItem::class, 'rfm_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {

            // Safety: generate only if empty
            if (empty($model->rfm_number)) {

                $lastId = static::withTrashed()
                    ->max('rfm_id') ?? 0;

                $nextId = $lastId + 1;

                $model->rfm_number = 'RFM-' . str_pad(
                    $nextId,
                    10,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
