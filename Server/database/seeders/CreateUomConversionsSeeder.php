<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UOM;
use Illuminate\Support\Facades\DB;

class CreateUomConversionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Creates conversion factors between related UOMs:
     * - Volume conversions: ml ↔ L
     * - Weight conversions: g ↔ kg
     * - Length conversions: cm ↔ m
     * - Area conversions: cm² ↔ m²
     */
    public function run()
    {
        $conversions = [];

        // ====== VOLUME CONVERSIONS ======
        // ml → larger units
        if ($this->uomExists('ml') && $this->uomExists('L')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('ml'),
                'to_uom_id' => $this->getUomId('L'),
                'conversion_factor' => 0.001, // 1ml = 0.001L
                'description' => '1 ml = 0.001 litre',
            ];
        }

        if ($this->uomExists('L') && $this->uomExists('ml')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('L'),
                'to_uom_id' => $this->getUomId('ml'),
                'conversion_factor' => 1000, // 1L = 1000ml
                'description' => '1 litre = 1000 ml',
            ];
        }

        // 250ml conversions
        if ($this->uomExists('250ml') && $this->uomExists('ml')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('250ml'),
                'to_uom_id' => $this->getUomId('ml'),
                'conversion_factor' => 250,
                'description' => '1 × 250ml = 250 ml',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('ml'),
                'to_uom_id' => $this->getUomId('250ml'),
                'conversion_factor' => 0.004,
                'description' => '1 ml = 0.004 × 250ml',
            ];
        }

        // 500ml conversions
        if ($this->uomExists('500ml') && $this->uomExists('ml')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('500ml'),
                'to_uom_id' => $this->getUomId('ml'),
                'conversion_factor' => 500,
                'description' => '1 × 500ml = 500 ml',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('ml'),
                'to_uom_id' => $this->getUomId('500ml'),
                'conversion_factor' => 0.002,
                'description' => '1 ml = 0.002 × 500ml',
            ];
        }

        // 750ml conversions
        if ($this->uomExists('750ml') && $this->uomExists('ml')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('750ml'),
                'to_uom_id' => $this->getUomId('ml'),
                'conversion_factor' => 750,
                'description' => '1 × 750ml = 750 ml',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('ml'),
                'to_uom_id' => $this->getUomId('750ml'),
                'conversion_factor' => 0.001333,
                'description' => '1 ml = 0.001333 × 750ml',
            ];
        }

        // dl conversions
        if ($this->uomExists('dl') && $this->uomExists('ml')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('dl'),
                'to_uom_id' => $this->getUomId('ml'),
                'conversion_factor' => 100,
                'description' => '1 dl = 100 ml',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('ml'),
                'to_uom_id' => $this->getUomId('dl'),
                'conversion_factor' => 0.01,
                'description' => '1 ml = 0.01 dl',
            ];
        }

        // ====== WEIGHT CONVERSIONS ======
        // g → larger units
        if ($this->uomExists('g') && $this->uomExists('kg')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('g'),
                'to_uom_id' => $this->getUomId('kg'),
                'conversion_factor' => 0.001, // 1g = 0.001kg
                'description' => '1 gram = 0.001 kilogram',
            ];
        }

        if ($this->uomExists('kg') && $this->uomExists('g')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('kg'),
                'to_uom_id' => $this->getUomId('g'),
                'conversion_factor' => 1000, // 1kg = 1000g
                'description' => '1 kilogram = 1000 grams',
            ];
        }

        // 250g conversions
        if ($this->uomExists('250g') && $this->uomExists('g')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('250g'),
                'to_uom_id' => $this->getUomId('g'),
                'conversion_factor' => 250,
                'description' => '1 × 250g = 250 g',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('g'),
                'to_uom_id' => $this->getUomId('250g'),
                'conversion_factor' => 0.004,
                'description' => '1 g = 0.004 × 250g',
            ];
        }

        // 500g conversions
        if ($this->uomExists('500g') && $this->uomExists('g')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('500g'),
                'to_uom_id' => $this->getUomId('g'),
                'conversion_factor' => 500,
                'description' => '1 × 500g = 500 g',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('g'),
                'to_uom_id' => $this->getUomId('500g'),
                'conversion_factor' => 0.002,
                'description' => '1 g = 0.002 × 500g',
            ];
        }

        // mg conversions
        if ($this->uomExists('mg') && $this->uomExists('g')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('mg'),
                'to_uom_id' => $this->getUomId('g'),
                'conversion_factor' => 0.001,
                'description' => '1 mg = 0.001 g',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('g'),
                'to_uom_id' => $this->getUomId('mg'),
                'conversion_factor' => 1000,
                'description' => '1 g = 1000 mg',
            ];
        }

        // ton conversions
        if ($this->uomExists('ton') && $this->uomExists('kg')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('ton'),
                'to_uom_id' => $this->getUomId('kg'),
                'conversion_factor' => 1000,
                'description' => '1 ton = 1000 kg',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('kg'),
                'to_uom_id' => $this->getUomId('ton'),
                'conversion_factor' => 0.001,
                'description' => '1 kg = 0.001 ton',
            ];
        }

        // ====== LENGTH CONVERSIONS ======
        // mm → larger units
        if ($this->uomExists('mm') && $this->uomExists('cm')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('mm'),
                'to_uom_id' => $this->getUomId('cm'),
                'conversion_factor' => 0.1,
                'description' => '1 mm = 0.1 cm',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('cm'),
                'to_uom_id' => $this->getUomId('mm'),
                'conversion_factor' => 10,
                'description' => '1 cm = 10 mm',
            ];
        }

        // cm → m
        if ($this->uomExists('cm') && $this->uomExists('m')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('cm'),
                'to_uom_id' => $this->getUomId('m'),
                'conversion_factor' => 0.01,
                'description' => '1 cm = 0.01 m',
            ];
        }

        if ($this->uomExists('m') && $this->uomExists('cm')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('m'),
                'to_uom_id' => $this->getUomId('cm'),
                'conversion_factor' => 100,
                'description' => '1 m = 100 cm',
            ];
        }

        // m → km
        if ($this->uomExists('m') && $this->uomExists('km')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('m'),
                'to_uom_id' => $this->getUomId('km'),
                'conversion_factor' => 0.001,
                'description' => '1 m = 0.001 km',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('km'),
                'to_uom_id' => $this->getUomId('m'),
                'conversion_factor' => 1000,
                'description' => '1 km = 1000 m',
            ];
        }

        // ====== AREA CONVERSIONS ======
        // cm² → m²
        if ($this->uomExists('cm²') && $this->uomExists('m²')) {
            $conversions[] = [
                'from_uom_id' => $this->getUomId('cm²'),
                'to_uom_id' => $this->getUomId('m²'),
                'conversion_factor' => 0.0001,
                'description' => '1 cm² = 0.0001 m²',
            ];
            $conversions[] = [
                'from_uom_id' => $this->getUomId('m²'),
                'to_uom_id' => $this->getUomId('cm²'),
                'conversion_factor' => 10000,
                'description' => '1 m² = 10000 cm²',
            ];
        }

        // Insert all conversions
        if (!empty($conversions)) {
            DB::table('uom_conversions')->insert($conversions);
        }
    }

    /**
     * Check if a UOM exists by abbreviation
     */
    private function uomExists(string $abbreviation): bool
    {
        return UOM::where('abbreviation', $abbreviation)->exists();
    }

    /**
     * Get UOM ID by abbreviation
     */
    private function getUomId(string $abbreviation): int
    {
        $uom = UOM::where('abbreviation', $abbreviation)->first();
        return $uom ? $uom->id : 0;
    }
}
