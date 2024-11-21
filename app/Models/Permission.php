<?php

namespace App\Models;

// Permission.php
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public function roles()
    {
        return $this->belongsToMany(Role::class,'permission_roles', 'permission_name', 'role_id', 'name', 'id');
    }
}
