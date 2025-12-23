<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ModelAccountCodes;
use App\Models\Items;
use App\Models\ModelMctItem;

class ModelDepreciationProfile extends Model
{
    use HasFactory;

    protected $table = 'tbl_depreciation_profiles';
    protected $primaryKey = 'id';

    protected $fillable = [
        'mct_item_id',
        'itemcode_id',

        'account_code',
        'account_percent_value',
        'lifespan_years',

        'original_cost',
        'book_value',

        'years_used_before_return',
        'remaining_life_years',

        'depreciation_rate',
        'monthly_depreciation',

        'acquisition_date',
        'depreciation_start_date',

        'accumulated_months',
        'accumulated_depreciation',

        'status'
    ];

    protected $casts = [
        'account_percent_value' => 'decimal:2',
        'lifespan_years'        => 'decimal:2',

        'original_cost'         => 'decimal:2',
        'book_value'            => 'decimal:2',

        'years_used_before_return' => 'decimal:2',
        'remaining_life_years'     => 'decimal:2',

        'depreciation_rate'        => 'decimal:2',
        'monthly_depreciation'     => 'decimal:2',

        'accumulated_depreciation' => 'decimal:2',

        'acquisition_date'         => 'date',
        'depreciation_start_date'  => 'date',

        'accumulated_months'       => 'integer',
    ];

    // RELATIONSHIP: belongs to the account code category
    public function accountCode()
    {
        return $this->belongsTo(ModelAccountCodes::class, 'account_code', 'account_code');
    }

    // RELATIONSHIP: belongs to MCT Item (optional depende sa struct)
    public function mctItem()
    {
        return $this->belongsTo(ModelMctItems::class, 'mct_item_id', 'mct_item_id');
    }

    // RELATIONSHIP: belongs to ItemCode
    public function itemCode()
    {
        return $this->belongsTo(Items::class, 'itemcode_id', 'ItemCode_id');
    }
}
