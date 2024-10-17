<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.id',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole('admin'); 

        $user = User::create([
            'name' => 'User',
            'email' => 'user@gmail.id',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole('user');
    }
}
