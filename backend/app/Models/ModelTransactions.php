<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelTransactions extends Model
{
    use HasFactory;

    protected $table = 'tbl_transactions'; // specify table name

    protected $primaryKey = 'transaction_id'; // specify primary key if not "id"

    protected $fillable = [
        'ItemCode_id',
        'movement_type',
        'transaction_type',
        'quantity',
        'remarks',
        'reference',
        'reference_type',
        'user_id',
        'created_by',
        'status',
        'updated_by',
    ];

      protected $attributes = [
        'status' => 'ACTIVE', // automatic default kapag nag-create
    ];
}
