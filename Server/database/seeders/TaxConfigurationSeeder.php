<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxConfiguration;
use Illuminate\Support\Facades\DB;

class TaxConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure no company-specific defaults are set
        DB::table('tax_configurations')->update(['is_default' => false]);

        // Create a single global set if not exists
        if (TaxConfiguration::whereNull('company_id')->count() === 0) {
            // Standard VAT - 16% (Default) - Global System Default
            TaxConfiguration::create([
                'company_id' => null,
                'name' => 'Standard VAT',
                'tax_type' => 'VAT',
                'rate' => 16.00,
                'is_inclusive' => false,
                'is_default' => true,
                'is_active' => true,
                'is_system_default' => true,
                'description' => 'Kenya standard VAT rate - 16% added to sale prices',
            ]);

            // Zero-Rated - Global System Default
            TaxConfiguration::create([
                'company_id' => null,
                'name' => 'Zero-Rated',
                'tax_type' => 'VAT',
                'rate' => 0.00,
                'is_inclusive' => false,
                'is_default' => false,
                'is_active' => true,
                'is_system_default' => true,
                'description' => 'Zero-rated items (exports, agricultural products, medical supplies)',
            ]);

            // Exempt - Global System Default
            TaxConfiguration::create([
                'company_id' => null,
                'name' => 'Exempt',
                'tax_type' => 'VAT',
                'rate' => 0.00,
                'is_inclusive' => false,
                'is_default' => false,
                'is_active' => true,
                'is_system_default' => true,
                'description' => 'Exempt items (financial services, education, residential rent)',
            ]);
        }
    }
}
