<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateUomTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapping of UOM names/abbreviations to their types
        $uomTypes = [
            // Volume UOMs
            'ml' => 'volume',
            '250ml' => 'volume',
            '500ml' => 'volume',
            '750ml' => 'volume',
            'dl' => 'volume',
            'L' => 'volume',
            'litre' => 'volume',
            'liter' => 'volume',
            'gallon' => 'volume',
            
            // Weight UOMs
            'mg' => 'weight',
            'g' => 'weight',
            'gram' => 'weight',
            '250g' => 'weight',
            '500g' => 'weight',
            'kg' => 'weight',
            'kilogram' => 'weight',
            'ton' => 'weight',
            
            // Length UOMs
            'mm' => 'length',
            'cm' => 'length',
            'm' => 'length',
            'meter' => 'length',
            'metre' => 'length',
            'km' => 'length',
            'inch' => 'length',
            'ft' => 'length',
            'foot' => 'length',
            
            // Area UOMs
            'm²' => 'area',
            'sq m' => 'area',
            'cm²' => 'area',
            'sq cm' => 'area',
            
            // Count/Pieces UOMs
            'pcs' => 'count',
            'piece' => 'count',
            'pc' => 'count',
            'dz' => 'count',
            'dozen' => 'count',
            'pack' => 'count',
            'pkt' => 'count',
            'packet' => 'count',
            'box' => 'count',
            'ctn' => 'count',
            'carton' => 'count',
            'crate' => 'count',
            'bottle' => 'count',
            'can' => 'count',
            'jar' => 'count',
            'bun' => 'count',
            'bundle' => 'count',
            'set' => 'count',
            'pair' => 'count',
        ];

        // Update UOMs based on their abbreviation or name
        foreach ($uomTypes as $uomIdentifier => $type) {
            DB::table('units_of_measure')
                ->where('abbreviation', $uomIdentifier)
                ->orWhere('name', $uomIdentifier)
                ->update(['type' => $type]);
        }

        // Fallback: set any remaining UOMs to 'other'
        DB::table('units_of_measure')
            ->where('type', '=', 'other')
            ->whereNull('type')
            ->update(['type' => 'other']);
    }
}
