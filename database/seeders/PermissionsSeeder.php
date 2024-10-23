<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        $adminPermissions = [
            ['name' => 'admin-create-users'],
            ['name' => 'admin-edit-users'],
            ['name' => 'admin-delete-users'],
            ['name' => 'admin-view-users'],
        ];

        $userPermissions = [
            ['name' => 'user-view-users'],
        ];

        foreach ($adminPermissions as $perm) {
            $permission = Permission::create($perm);

            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->permissions()->attach($permission->name, [  
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        foreach ($userPermissions as $perm) {
            $permission = Permission::create($perm);

            $userRole = Role::where('name', 'user')->first();
            if ($userRole) {
                $userRole->permissions()->attach($permission->name, [ 
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
