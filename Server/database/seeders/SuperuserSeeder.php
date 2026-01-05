<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperuserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'Superuser'],
            ['description' => 'Platform superuser']
        );

        User::updateOrCreate(
            ['email' => 'superuser@example.com'],
            [
                'name' => 'Superuser',
                'password' => Hash::make('123456'),
                'role_id' => $role->id,
                'verified' => true,
                'must_change_password' => false,
            ]
        );
    }
}
