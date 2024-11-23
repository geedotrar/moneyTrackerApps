<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialAccount extends Model
{
    use SoftDeletes;

    protected $table = 'financial_accounts';  
    
    protected $fillable = [
        'name',
    ];

    /**
     * Relationship to FinancialAccountBalance.
     */
    public function balances()
    {
        return $this->hasMany(Balance::class, 'financial_accounts_id');
    }

}
