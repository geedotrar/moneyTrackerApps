<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';  

    protected $fillable = ['name'];

    public function permissions():BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,          // model
            'permission_roles',   // pivot table name
            'role_id',            // foreign key in the pivot table (Role)
            'permission_name',    // foreign key in the pivot table (Permission)
            'id',                  // primary key in the `roles` table
            'name'                // primary key in the `permissions` table
        );
    }
    
    public function users():BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
