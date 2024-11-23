<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Balance extends Model
{
    use SoftDeletes;

    protected $table = 'balances';  
    
    protected $fillable = [
        'user_id',
        'financial_account_id',
        'amount'
    ];

    /**
     * Relationship to FinancialAccount.
     */
    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'financial_account_id');
    }

    /**
     * Relationship to User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->amount, 0, ',', '.');
    }
}
