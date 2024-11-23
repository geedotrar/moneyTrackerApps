<?php

namespace App\Models;

// Permission.php
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $table = 'permissions';  

    protected $fillable = ['name'];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,          // model
            'permission_roles',   // pivot table name
            'permission_name',    // foreign key in the pivot table (Permission)
            'role_id',            // foreign key in the pivot table (Role)
            'name',               // primary key in the `permissions` table
            'id'                  // primary key in the `roles` table
        );
    }
}
