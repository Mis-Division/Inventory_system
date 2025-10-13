<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class suppliers extends Model
{
    protected $table = 'tbl_suppliers';
    protected $primaryKey = 'supplier_id';
    protected $fillable = ['supplier_no', 'supplier_name', 'email','contact_no','address','tin','vat_no'];

}
