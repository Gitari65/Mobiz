<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PriceGroup;

class PriceGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priceGroups = [
            [
                'name' => 'Retail',
                'code' => 'RETAIL',
                'description' => 'Standard retail pricing for individual customers',
                'discount_percentage' => 0.00,
                'is_system' => true,
                'company_id' => null
            ],
            [
                'name' => 'Stockist',
                'code' => 'STOCKIST',
                'description' => 'Wholesale pricing for stockist partners',
                'discount_percentage' => 10.00,
                'is_system' => true,
                'company_id' => null
            ],
            [
                'name' => 'Superstockist',
                'code' => 'SUPERSTOCKIST',
                'description' => 'Premium wholesale pricing for superstockist partners',
                'discount_percentage' => 15.00,
                'is_system' => true,
                'company_id' => null
            ]
        ];

        foreach ($priceGroups as $priceGroup) {
            PriceGroup::firstOrCreate(
                ['code' => $priceGroup['code']],
                $priceGroup
            );
        }
    }
}
