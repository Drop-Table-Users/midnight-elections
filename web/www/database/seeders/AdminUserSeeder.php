<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if admin user already exists
        $adminEmail = 'admin@sk-elections.com';

        if (!User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => 'Admin User',
                'email' => $adminEmail,
                'password' => Hash::make('password'), // Change this in production!
                'email_verified_at' => now(),
            ]);

            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: ' . $adminEmail);
            $this->command->info('Password: password (Please change this!)');
        } else {
            $this->command->info('Admin user already exists.');
        }
    }
}
