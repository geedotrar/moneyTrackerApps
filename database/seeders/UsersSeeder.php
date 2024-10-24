<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@admin.com',
                'password' => bcrypt('password'),
            ],
            [
                'name' => 'User',
                'username' => 'user',
                'email' => 'user@user.com',
                'password' => bcrypt('password'),
            ],
        ];

        foreach ($users as $user) {
            if (User::where('email', $user['email'])->doesntExist()) {
                User::create($user);
            }
        }
    }
}
