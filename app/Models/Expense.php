<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'sub_category_id',
        'payment_method',
        'payment_method_name',
        'amount',
        'description',
        'date',
    ];
}