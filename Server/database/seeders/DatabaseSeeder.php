<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            // System defaults that all companies inherit
            \Database\Seeders\TaxConfigurationSeeder::class,
            \Database\Seeders\PlanSeeder::class,
            // ...other seeders...
            \Database\Seeders\SuperuserSeeder::class,
            \Database\Seeders\PaymentMethodSeeder::class,
            \Database\Seeders\WarehouseSeeder::class,
        ]);
    }
}
