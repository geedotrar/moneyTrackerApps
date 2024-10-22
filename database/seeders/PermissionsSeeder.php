<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['name' => 'create-users'],
            ['name' => 'edit-users'],
            ['name' => 'delete-users'],
            ['name' => 'view-users'],
        ];

        foreach ($permissions as $permission) {
            if (Permission::where('name', $permission['name'])->doesntExist()) {
                Permission::create($permission);
            }
        }
    }
}
