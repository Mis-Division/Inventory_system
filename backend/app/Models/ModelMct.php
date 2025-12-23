<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelMct extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_mct';
    protected $primaryKey = 'mct_id';

    protected $fillable = [
        'mct_number',
        'mrv_id',
        'mrv_number',
        'product_type',
        'requested_by',
        'issued_by',
        'received_by',
        'remarks',
        'status',
        'grand_total',
    ];

    public function items()
    {
        return $this->hasMany(ModelMctItems::class, 'mct_id', 'mct_id');
    }

    /**
     * Auto-generate MCT Number
     * Format → MCT No.000001
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            $latest = self::orderBy('mct_id', 'desc')->first();

            $nextNumber = 1;

            if ($latest && $latest->mct_number) {

                // Safely extract the LAST 6 digits only
                preg_match('/(\d{6})$/', $latest->mct_number, $matches);

                if (isset($matches[1])) {
                    $nextNumber = intval($matches[1]) + 1;
                }
            }

            // Create final output: MCT No.000001
            $model->mct_number = 'MCT No.' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
public function requestedByUser()
{
    return $this->belongsTo(User::class, 'requested_by', 'sige');
}


}
