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
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Admin User', 'is_admin' => true , 'password' => Hash::make("password")],
        );

        User::firstOrCreate(
            ['email' => 'test@test.com'],
            ['name' => 'Test User', 'is_admin' => false , 'password' => Hash::make("password")],
        );
    }
}
