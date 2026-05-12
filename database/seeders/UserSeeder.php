<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Admin User', 'password' => Hash::make("password")],
        );
        $admin->assignRole('admin');

        User::firstOrCreate(
            ['email' => 'test@test.com'],
            ['name' => 'Test User', 'password' => Hash::make("password")],
        );
    }
}
