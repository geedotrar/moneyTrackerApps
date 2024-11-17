<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Balance extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'financial_account_id',
        'amount'
    ];

    /**
     * Relationship to FinancialAccount.
     */
    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /**
     * Relationship to User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->amount, 0, ',', '.');
    }
}
