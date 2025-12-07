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
        'AccountCode',
        'supplier_name',
        'address',
        'contact_no',
        'contact_person',
        'tin',
        'vat_type',
    ];

    public function received()
    {
        return $this->hasMany(ModelReceived::class, 'supplier_id');
    }
}
