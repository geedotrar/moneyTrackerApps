<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'cateogries';  
    
    protected $fillable = [
        'name',
        'description',
        'type'
    ];

    public function subCategories():HasMany
    {
        return $this->hasMany(SubCategory::class);
    }
}
