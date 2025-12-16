<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Prevent duplicate admin
        $admin = User::where('email', 'admin@mavambo.com')->first();

        if (!$admin) {
            User::create([
                'name' => 'System Admin',
                'email' => 'admin@mavambo.com',
                'password' => Hash::make('admin12345'),
                'phone' => '+263785009885',
                'location' => 'Harare',
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }
    }
}
