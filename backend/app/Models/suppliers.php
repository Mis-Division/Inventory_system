<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Suppliers extends Model
{
    use HasFactory;

    protected $table = 'tbl_suppliers';
    protected $primaryKey = 'supplier_id';

    protected $fillable = [
        'supplier_no',
        'supplier_name',
        'email',
        'contact_no',
        'address',
        'tin',
        'vat_no',
    ];

    public function received()
    {
        return $this->hasMany(ModelReceived::class, 'supplier_id');
    }
}
