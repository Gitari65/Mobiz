<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UOM;

class CreateDefaultUomsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates default system UOMs organized by type:
     * - Volume: ml, L and related
     * - Weight: g, kg and related
     * - Length: cm, m and related
     * - Count: pcs and related packaging units
     * - Area: m² and related
     */
    public function run()
    {
        $defaultUOMs = [
            // VOLUME - Smaller to Larger
            ['name' => 'Millilitre', 'abbreviation' => 'ml', 'type' => 'volume', 'description' => 'Small liquid measurement'],
            ['name' => '250 Millilitre', 'abbreviation' => '250ml', 'type' => 'volume', 'description' => 'Quarter litre bottle'],
            ['name' => '500 Millilitre', 'abbreviation' => '500ml', 'type' => 'volume', 'description' => 'Half litre bottle'],
            ['name' => '750 Millilitre', 'abbreviation' => '750ml', 'type' => 'volume', 'description' => 'Three-quarter litre bottle'],
            ['name' => 'Decilitre', 'abbreviation' => 'dl', 'type' => 'volume', 'description' => 'One-tenth litre'],
            ['name' => 'Litre', 'abbreviation' => 'L', 'type' => 'volume', 'description' => 'Standard liquid measurement'],
            ['name' => 'Litre', 'abbreviation' => 'litre', 'type' => 'volume', 'description' => 'Alternative to L'],

            // WEIGHT - Smaller to Larger
            ['name' => 'Milligram', 'abbreviation' => 'mg', 'type' => 'weight', 'description' => 'Small weight measurement'],
            ['name' => 'Gram', 'abbreviation' => 'g', 'type' => 'weight', 'description' => 'Basic weight unit'],
            ['name' => '250 Gram', 'abbreviation' => '250g', 'type' => 'weight', 'description' => 'Quarter kilogram'],
            ['name' => '500 Gram', 'abbreviation' => '500g', 'type' => 'weight', 'description' => 'Half kilogram'],
            ['name' => 'Kilogram', 'abbreviation' => 'kg', 'type' => 'weight', 'description' => 'Standard weight measurement'],
            ['name' => 'Ton', 'abbreviation' => 'ton', 'type' => 'weight', 'description' => 'Large weight measurement'],

            // LENGTH - Smaller to Larger
            ['name' => 'Millimetre', 'abbreviation' => 'mm', 'type' => 'length', 'description' => 'Small length measurement'],
            ['name' => 'Centimetre', 'abbreviation' => 'cm', 'type' => 'length', 'description' => 'Centimetre measurement'],
            ['name' => 'Metre', 'abbreviation' => 'm', 'type' => 'length', 'description' => 'Standard length measurement'],
            ['name' => 'Metre', 'abbreviation' => 'meter', 'type' => 'length', 'description' => 'Alternative to m'],
            ['name' => 'Kilometre', 'abbreviation' => 'km', 'type' => 'length', 'description' => 'Large distance measurement'],

            // AREA
            ['name' => 'Square Metre', 'abbreviation' => 'm²', 'type' => 'area', 'description' => 'Area measurement'],
            ['name' => 'Square Centimetre', 'abbreviation' => 'cm²', 'type' => 'area', 'description' => 'Small area measurement'],

            // COUNT - Individual Items & Packaging
            ['name' => 'Piece', 'abbreviation' => 'pcs', 'type' => 'count', 'description' => 'Individual item'],
            ['name' => 'Piece', 'abbreviation' => 'pc', 'type' => 'count', 'description' => 'Alternative to pcs'],
            ['name' => 'Dozen', 'abbreviation' => 'dz', 'type' => 'count', 'description' => 'Set of 12 items'],
            ['name' => 'Box', 'abbreviation' => 'box', 'type' => 'count', 'description' => 'Boxed items'],
            ['name' => 'Carton', 'abbreviation' => 'ctn', 'type' => 'count', 'description' => 'Carton packaging'],
            ['name' => 'Pack', 'abbreviation' => 'pack', 'type' => 'count', 'description' => 'Package of items'],
            ['name' => 'Packet', 'abbreviation' => 'pkt', 'type' => 'count', 'description' => 'Small packet'],
            ['name' => 'Bottle', 'abbreviation' => 'bottle', 'type' => 'count', 'description' => 'Bottled product'],
            ['name' => 'Can', 'abbreviation' => 'can', 'type' => 'count', 'description' => 'Canned product'],
            ['name' => 'Jar', 'abbreviation' => 'jar', 'type' => 'count', 'description' => 'Jar packaging'],
            ['name' => 'Bundle', 'abbreviation' => 'bundle', 'type' => 'count', 'description' => 'Bundled items'],
            ['name' => 'Pair', 'abbreviation' => 'pair', 'type' => 'count', 'description' => 'Set of 2 items'],
            ['name' => 'Set', 'abbreviation' => 'set', 'type' => 'count', 'description' => 'Set of items'],
        ];

        // Check if any UOMs already exist to avoid duplicates
        $existingCount = UOM::count();

        foreach ($defaultUOMs as $uom) {
            // Check if abbreviation already exists (to prevent duplicates on re-run)
            if (!UOM::where('abbreviation', $uom['abbreviation'])->exists()) {
                UOM::create([
                    'name' => $uom['name'],
                    'abbreviation' => $uom['abbreviation'],
                    'description' => $uom['description'],
                    'is_system' => true,
                    'type' => $uom['type'],
                ]);
            }
        }
    }
}
