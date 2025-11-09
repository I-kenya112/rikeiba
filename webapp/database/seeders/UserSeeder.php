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
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'user_id' => 'admin001',
                'name' => '管理者',
                'email' => 'admin@example.com',
                'password' => Hash::make('password-rikeiba'),
                'email_verified_at' => now(),
                'role' => 'admin',
            ]
        );
    }
}
