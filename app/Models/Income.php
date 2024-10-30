<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'amount',
        'source',
        'description',
        'date',
        'payment_method',
        'payment_method_name',
    ];
}
