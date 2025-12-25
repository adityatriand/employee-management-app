<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create admin user
        User::create([
            'name' => 'Administrator',
            'email' => 'admin@hrms.com',
            'password' => Hash::make('admin123'),
            'level' => 1, // Admin level
        ]);

        // Create regular user
        User::create([
            'name' => 'User Test',
            'email' => 'user@hrms.com',
            'password' => Hash::make('user123'),
            'level' => 0, // Regular user
        ]);
    }
}

