<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxConfiguration;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

class TaxConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        // Clean up ALL old tax configs (reset for fresh multi-tenant setup)
        // Always delete NULL company_id entries
        TaxConfiguration::whereNull('company_id')->delete();

        // Get the first company (system assumes at least one exists at startup)
        $company = Company::first();
        
        if (!$company) {
            $this->command->info('No company found. Skipping tax configuration seeding.');
            return;
        }

        $companyId = $company->id;

        // Delete existing configs for this company to ensure fresh state
        TaxConfiguration::where('company_id', $companyId)->delete();
        $this->command->info('Cleared existing tax configurations for company: ' . $company->name);

        // Create Kenya tax defaults for this company
        $taxes = [
            [
                'name' => 'Standard VAT',
                'tax_type' => 'VAT',
                'rate' => 16.00,
                'is_inclusive' => false,
                'is_default' => true,
                'is_active' => true,
                'is_system_default' => true,
                'description' => 'Kenya standard VAT rate - 16% added to sale prices',
            ],
            [
                'name' => 'Zero-Rated',
                'tax_type' => 'VAT',
                'rate' => 0.00,
                'is_inclusive' => false,
                'is_default' => false,
                'is_active' => true,
                'is_system_default' => true,
                'description' => 'Zero-rated items (exports, agricultural products, medical supplies)',
            ],
            [
                'name' => 'Exempt',
                'tax_type' => 'VAT',
                'rate' => 0.00,
                'is_inclusive' => false,
                'is_default' => false,
                'is_active' => true,
                'is_system_default' => true,
                'description' => 'Exempt items (financial services, education, residential rent)',
            ],
        ];

        foreach ($taxes as $tax) {
            TaxConfiguration::create(array_merge($tax, ['company_id' => $companyId]));
        }

        $this->command->info('Tax configurations seeded for company: ' . $company->name);
    }
}
