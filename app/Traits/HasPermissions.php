<?php

namespace App\Traits;

use App\Models\Role;

trait HasPermissions
{
    public function hasPermission(string $permissionName): bool
    {
        $roles = $this->roles()->with('permissions')->get();

        foreach ($roles as $role) {
            if ($role->permissions->pluck('name')->contains($permissionName)) {
                return true;
            }
        }

        return false;
    }
}
