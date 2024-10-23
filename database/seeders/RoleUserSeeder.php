<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class RoleUserSeeder extends Seeder
{
    public function run()
    {
        $adminUser = User::where('email', 'admin@admin.com')->first();
        $normalUser = User::where('email', 'user@user.com')->first();

        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        if ($adminUser && $adminRole) {
            $adminUser->roles()->attach($adminRole);
        }

        if ($normalUser && $userRole) {
            $normalUser->roles()->attach($userRole);
        }
    }
}
