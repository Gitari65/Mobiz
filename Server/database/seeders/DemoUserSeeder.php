<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        $superuserRole = Role::firstOrCreate(['name' => 'superuser']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);

        // Create demo superuser
        User::firstOrCreate(
            ['email' => 'superuser@mobiz.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password123'),
                'role_id' => $superuserRole->id,
                'verified' => true,
            ]
        );

        // Create demo admin
        User::firstOrCreate(
            ['email' => 'admin@mobiz.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password123'),
                'role_id' => $adminRole->id,
                'verified' => true,
            ]
        );

        // Create demo cashier
        User::firstOrCreate(
            ['email' => 'cashier@mobiz.com'],
            [
                'name' => 'Cashier User',
                'password' => Hash::make('password123'),
                'role_id' => $cashierRole->id,
                'verified' => true,
            ]
        );
    }
}
