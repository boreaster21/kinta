<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist first
        $adminRole = Role::where('name', 'admin')->first();
        $userRole = Role::where('name', 'user')->first();

        // Create specific Admin User if not exists
        User::factory()->state([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('adminpass'),
            'role_id' => $adminRole->id ?? Role::factory()->state(['name' => 'admin']),
            'email_verified_at' => now(),
        ])->create();

        // Create specific Test User if not exists
        User::factory()->state([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('testpass'),
            'role_id' => $userRole->id ?? Role::factory()->state(['name' => 'user']),
            'email_verified_at' => now(),
        ])->create();

        // Create additional random users
        User::factory()->count(18)->state([
            'role_id' => $userRole->id ?? Role::factory()->state(['name' => 'user'])
        ])->create();
    }
}
