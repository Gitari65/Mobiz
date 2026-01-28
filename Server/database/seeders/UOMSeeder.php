<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UOMSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uoms = [
            // Volume - Liquid
            ['name' => '250 ml', 'abbreviation' => '250ml', 'description' => '250 Milliliters', 'is_system' => true],
            ['name' => '500 ml', 'abbreviation' => '500ml', 'description' => '500 Milliliters', 'is_system' => true],
            ['name' => '750 ml', 'abbreviation' => '750ml', 'description' => '750 Milliliters', 'is_system' => true],
            ['name' => '1 Liter', 'abbreviation' => '1L', 'description' => '1 Liter', 'is_system' => true],
            ['name' => 'Milliliter', 'abbreviation' => 'ml', 'description' => 'Milliliter', 'is_system' => true],
            ['name' => 'Liter', 'abbreviation' => 'L', 'description' => 'Liter', 'is_system' => true],
            
            // Weight
            ['name' => 'Kilogram', 'abbreviation' => 'kg', 'description' => 'Kilogram', 'is_system' => true],
            ['name' => 'Gram', 'abbreviation' => 'g', 'description' => 'Gram', 'is_system' => true],
            ['name' => '250g', 'abbreviation' => '250g', 'description' => '250 Grams', 'is_system' => true],
            ['name' => '500g', 'abbreviation' => '500g', 'description' => '500 Grams', 'is_system' => true],
            
            // Count/Pieces
            ['name' => 'Piece', 'abbreviation' => 'pcs', 'description' => 'Piece/Unit', 'is_system' => true],
            ['name' => 'Dozen', 'abbreviation' => 'dz', 'description' => '12 Pieces', 'is_system' => true],
            ['name' => 'Pack', 'abbreviation' => 'pack', 'description' => 'Package', 'is_system' => true],
            ['name' => 'Carton', 'abbreviation' => 'ctn', 'description' => 'Carton', 'is_system' => true],
            ['name' => 'Box', 'abbreviation' => 'box', 'description' => 'Box', 'is_system' => true],
            ['name' => 'Bottle', 'abbreviation' => 'bottle', 'description' => 'Bottle', 'is_system' => true],
            ['name' => 'Can', 'abbreviation' => 'can', 'description' => 'Can', 'is_system' => true],
            ['name' => 'Packet', 'abbreviation' => 'pkt', 'description' => 'Packet', 'is_system' => true],
            ['name' => 'Bundle', 'abbreviation' => 'bun', 'description' => 'Bundle', 'is_system' => true],
            
            // Length
            ['name' => 'Meter', 'abbreviation' => 'm', 'description' => 'Meter', 'is_system' => true],
            ['name' => 'Centimeter', 'abbreviation' => 'cm', 'description' => 'Centimeter', 'is_system' => true],
            
            // Area
            ['name' => 'Square Meter', 'abbreviation' => 'm²', 'description' => 'Square Meter', 'is_system' => true],
        ];

        foreach ($uoms as $uom) {
            \App\Models\UOM::updateOrCreate(
                ['abbreviation' => $uom['abbreviation']],
                $uom
            );
        }
    }
}
