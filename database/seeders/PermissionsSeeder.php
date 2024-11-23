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
            //USER
            ['name' => 'admin-create-users'],
            ['name' => 'admin-edit-users'],
            ['name' => 'admin-delete-users'],
            ['name' => 'admin-view-users'],

            //BALANCES
            ['name' => 'admin-create-balances'],
            ['name' => 'admin-edit-balances'],
            ['name' => 'admin-delete-balances'],
            ['name' => 'admin-view-balances'],
        ];

        $userPermissions = [
            //USER
            ['name' => 'user-view-users'],
            ['name' => 'user-edit-users'],

            //BALANCES
            ['name' => 'user-view-balances'],
            ['name' => 'user-edit-balances'],
        ];

        // Seed admin permissions
        foreach ($adminPermissions as $perm) {
            $permission = Permission::firstOrCreate($perm);

            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->permissions()->syncWithoutDetaching($permission->name, [  
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }

        // Seed user permissions
        foreach ($userPermissions as $perm) {
            $permission = Permission::firstOrCreate($perm);

            $userRole = Role::where('name', 'user')->first();
            if ($userRole) {
                $userRole->permissions()->syncWithoutDetaching($permission->name, [ 
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
