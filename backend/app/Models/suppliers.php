<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class suppliers extends Model
{
    protected $table = 'tbl_suppliers';
    protected $primaryKey = 'supplier_id';
    protected $fillable = ['supplier_name', 'contact_person', 'email','phone','address'];

}
