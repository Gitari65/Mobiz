<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Seeds two default system-wide warehouse types:
     * 1. Main - Primary warehouse for storing regular inventory
     * 2. Breakages - Warehouse for damaged/broken items
     * 
     * These warehouses have null company_id, making them 
     * system defaults that all companies can reference.
     * Admins can create additional company-specific warehouses.
     */
    public function run(): void
    {
        $defaultWarehouses = [
            [
                'name' => 'Main Warehouse',
                'type' => 'main',
                'company_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Breakages Warehouse',
                'type' => 'breakages',
                'company_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($defaultWarehouses as $warehouse) {
            // Only create if it doesn't already exist (by type)
            Warehouse::firstOrCreate(
                ['type' => $warehouse['type'], 'company_id' => null],
                $warehouse
            );
        }

        $this->command->info('✓ Default warehouses seeded successfully!');
    }
}
