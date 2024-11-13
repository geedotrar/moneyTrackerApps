<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'sub_category_id',
        'financial_account_id',
        'amount',
        'description',
        'date',
    ];

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function financialAccount():BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class,'financial_account_id');
    }

    public function subCategory():BelongsTo
    {
        return $this->belongsTo(SubCategory::class,'sub_category_id');
    }
}
