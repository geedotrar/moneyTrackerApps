<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class RoleUserSeeder extends Seeder
{
    public function run()
    {
        $user1 = User::where('email', 'admin@mail.com')->first();
        // $user2 = User::where('email', 'user@mail.com')->first();
        $adminRole = Role::where('name', 'admin')->first();
        // $userRole = Role::where('name', 'user')->first();

        if ($user1 && $adminRole) {
            $user1->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // if ($user2 && $userRole) {
        //     $user2->roles()->syncWithoutDetaching([$userRole->id]);
        // }
    }
}
