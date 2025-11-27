<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelReceived extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tbl_receive';
    protected $primaryKey = 'r_id';
    public $timestamps = true;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'po_number',
        'invoice_number',
        'supplier_id',
        'dr_number',
        'rr_number',   
        'received_by',
        'receive_date',
        'grand_total',
        'remarks',
    ];

    /**
     * Relationships
     */
    public function supplier()
    {
        return $this->belongsTo(Suppliers::class, 'supplier_id');
    }

    public function receivedItems()
    {
        return $this->hasMany(ModelReceivedItem::class, 'r_id');
    }
   

    /**
     * Automatically generate RR number before creating a record
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Get latest record
            $latest = self::orderBy('r_id', 'desc')->first();

            // Determine next RR number
            $nextNumber = 1;
            if ($latest && $latest->rr_number) {
                $latestNum = (int) preg_replace('/[^0-9]/', '', $latest->rr_number);
                $nextNumber = $latestNum + 1;
            }

            // Assign formatted RR number
            $model->rr_number = 'RR No. ' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }
}
