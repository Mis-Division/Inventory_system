<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelAccountCodes extends Model
{
    use HasFactory;

    protected $table = 'tbl_account_codes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'account_code',
        'description',
        'percent_value',
        'account_type', // 1 = asset, 0 = non-asset
    ];

    protected $casts = [
        'percent_value' => 'decimal:2',
        'account_type'  => 'integer',
    ];

    // RELATIONSHIP: One account code may apply to many depreciation profiles
    public function depreciationProfiles()
    {
        return $this->hasMany(ModelDepreciationProfile::class, 'account_code', 'account_code');
    }
}
